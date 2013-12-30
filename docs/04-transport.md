Transport
=========

After your project is build it will be transferred to the selected target. Conveyor supports a lot
of different transport protocols.

## Transporters

Each transfer protocol is handled by a specific transporter. Below are the available transporters.

### FileTransporter

Can be used to deploy to the local filesystem.

Example:

	targets:
	  production:
	    transporter:
	      type: file
	      host: ~
	      path: /tmp/my-project
	      user: ~
	      pass: ~

### FtpTransporter

Transfer using FTP.

### SftpTransporter (recommended)

Transfer using SFTP. The SFTP is currently the most mature transporter in Conveyor and therefore recommended to use.

Example:

	targets:
	  production:
	    transporter:
	      type: sftp
	      host: example.com
	      path: public_html/example.com
	      user: user
	      pass: userpass

You can use ssh key authentication by setting `pass` to `~`:

    pass: ~

This will look for your key in `~/.ssh/id_rsa`. If the key contains a passphrase you will be prompt about it. Your passphrase will NEVER be stored.

### RsyncTransporter

_Note: This transporter is still experimental._

### ScpTransporter

_Note: This transporter is still experimental._

### GitTransporter

_Note: This transporter is still experimental._

Can be used to transfer a new version using git (hooks).

Available options:

- `url`: git url, for example: "git@github.com:acme/example.git"

Example:

    targets:
      production:
        transporter:
          type: git
          url:  git@github.com:acme/example.git

