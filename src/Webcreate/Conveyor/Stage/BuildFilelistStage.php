<?php

/*
 * This file is part of the Conveyor package.
 *
 * (c) Jeroen Fiege <jeroen@webcreate.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Webcreate\Conveyor\Stage;

use Webcreate\Conveyor\Util\FileCollection;
use Webcreate\Vcs\Common\Status;
use Webcreate\Conveyor\Context;
use Webcreate\Conveyor\Repository\Repository;

class BuildFilelistStage extends AbstractStage
{
    protected $repository;
    protected $derivedFiles;

    /**
     * @var Context
     */
    protected $context;

    public function __construct(Repository $repository, array $derivedFiles = array())
    {
        $this->repository = $repository;
        $this->derivedFiles = $derivedFiles;
    }

    public function supports(Context $context)
    {
        return true;
    }

    public function execute(Context $context)
    {
        // this was filled by the builder
        $filesModified = $context->getFilesModified();
        $filesDeleted = $context->getFilesDeleted();

        if (false === $context->isFullDeploy()) {
            $diff = $this->repository->diff($context->getRemoteVersion(), $context->getVersion());

            $subselectionModified = new FileCollection($context->getBuilddir());

            foreach ($diff as $fileinfo) {
                if (Status::ADDED === $fileinfo->getStatus()) {
                    $subselectionModified[] = $fileinfo->getPathname();
                } elseif (Status::MODIFIED === $fileinfo->getStatus()) {
                    $subselectionModified[] = $fileinfo->getPathname();
                } elseif (Status::DELETED === $fileinfo->getStatus()) {
                    $filesDeleted->add($fileinfo->getPathname(), true);
                } else {
                    // @todo handle other cases if they actually exist
                }
            }

            foreach ($this->derivedFiles as $derivable) {
                $source = $derivable['source'];
                $derived = $derivable['derived'];

                if ($subselectionModified->has($source)) {
                    $subselectionModified->add($derived);
                }
            }

            // only keep files that are changed
            $filesModified->intersect($subselectionModified);

//            if (0 === count($filesAdded) && count($subselectionAdded) > 0) {
//                // hmm.. so files have changed but nothing to deploy
//                // it looks like we forgot to specify some derived files
//                // @todo inform the user about this?
//            }

            // @todo we could also check here if all files are accounted for (maybe
            //       some files were deleted and not told to us by setting it in
            //       the `removes` config?) in other words: file_exists on all files?
        }

        // never upload the conveyor configuration!
        $filesModified->remove('conveyor.yml');

        // validate result, throw exception when we have nothing to deploy
        // @todo improve this, throwing exceptions is crap!
        if (0 === count($filesModified) && 0 === count($filesDeleted)) {
            throw new \RuntimeException('Nothing to deploy, this should not have happend');
        }

        $context->setFilesModified($filesModified);
        $context->setFilesDeleted($filesDeleted);
    }
}
