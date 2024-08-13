# upLoadly

A simple file temporary file upload service that allows you to upload files and share them with others. The files are stored for a limited time and then automatically deleted.

## Features

- :rocket: **Fast and easy to use**: Just drag and drop your files and share the link with others.
- :lock: **Secure**: All files are automatically deleted after a certain time.
- :globe_with_meridians: **Cross-platform**: Works on all platforms and devices.
- :open_file_folder: **No registration required**: Just upload your files and share the link.
- :zap: **Lightning fast**: No waiting times, no ads, no tracking.
- :sparkles: **Customizable**: Customize the look and feel of the upload page.
- :package: **Self-hosted**: You can host it yourself and customize it to your needs.

## Installation

1. Clone the repository:

```bash
git clone https://github.com/JMcrafter26/upLoadly.git
```

1. Install the dependencies:

```bash
composer install
```

1. Add a cron job to delete old files:

> Run cron.php every hour to delete old files.

```bash
0 * * * * php /path/to/upLoadly/cron.php
```

1. Customize and enjoy!

## Disclaimer

> :warning: This project is still in development and not yet ready for production use. Use at your own risk.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
