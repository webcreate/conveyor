Transport
=========

After your project is build it will be transferred to the selected target. Conveyor supports a lot
of different transport protocols.

## Transporters

Each transfer protocol is handled by a specific transporter. Below are the available transporters.

### FileTransporter

Can be used to deploy to the local filesystem.

### FtpTransporter

Transfer using FTP.

### SftpTransporter

Transfer using SFTP.

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

