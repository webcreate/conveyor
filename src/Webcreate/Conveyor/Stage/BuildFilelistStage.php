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

use Symfony\Component\Finder\Finder;

class BuildFilelistStage extends AbstractStage
{
    protected $repository;
    protected $derivedFiles;

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
        $filelist = $context->getFilelist(); // this was filled by the builder

        if (false === $context->isFullDeploy()) {
            $diff = $this->repository->diff($context->getRemoteVersion(), $context->getVersion());

            $subselection = new FileCollection($context->getBuilddir());

            foreach ($diff as $fileinfo) {
                if (in_array($fileinfo->getStatus(), array(Status::ADDED, Status::MODIFIED))) {
                    $subselection[] = $fileinfo->getPathname();
                }
                /*
                elseif ($fileinfo->getStatus() === Status::DELETED) {
                    // skip
                } else {
                    throw new \RuntimeException(sprintf('Unimplemented action \'%s\'', $fileinfo->getStatus()));
                }
                */
            }

            foreach($this->derivedFiles as $derivable) {
                $source = $derivable['source'];
                $derived = $derivable['derived'];

                if ($subselection->has($source)) {
                    $subselection->add($derived);
                }
            }

            // only keep files that are changed
            $filelist->intersect($subselection);

            if (0 === count($filelist) && count($subselection) > 0) {
                // hmm.. so files have changed but nothing to deploy
                // it looks like we forgot to specify some derived files
                // @todo inform the user about this?
            }

            // @todo we could also check here if all files are accounted for (maybe
            //       some files were deleted and not told to us by setting it in
            //       the `removes` config?) in other words: file_exists on all files?
        }

        // validate result, throw exception when we have nothing to deploy
        // @todo improve this, throwing exceptions is crap!
        if (0 === count($filelist)) {
            throw new \RuntimeException('Nothing to deploy, this should not have happend');
        }

        $context->setFilelist($filelist);
    }
}
