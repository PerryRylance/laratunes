# Laratunes
Laratunes is an open source streaming radio, RTMP client and web interface.

## Features
- Administrative interface for track management
- Audio fingerprinting for detecting duplicates on upload ~~and for users to find tracks by audio search~~ _(Coming soon!)_
- Streaming audio and a still image or video loop via RTMP (YouTube)
- QR code link to track embedded in video
- Nightbot YouTube chat integration
- Voting system
- Play count based shuffle ~~with weighting for upvoted tracks~~ _(Coming soon!)_
- ~~User suggested metadata~~ _(Coming soon!)_
- ~~User song requests~~ _(Coming soon!)_

## Deployment
> TODO: Write out this documentation

## Development

### Installation
- Clone this repository
- Run `composer install`
- Run `sail npm install`

### Starting the containers
This project uses [Laravel Sail](https://laravel.com/docs/12.x/sail) to run in development.

`sail up -d`

### Testing
Tests can be run with `sail test`.

### Streaming locally
You can stream to `ffplay` locally to test your streams output.

- In your `.env` set `BROADCAST_URL` to `rtmp://host.docker.internal` (in quotes)
- Optionally, set a `MEDIA_PATH` into your `.env` (after changing this, you will need to `sail up -d --force-recreate` if you already started `sail`)
- Run `sail app:discover-media` to 
- On your host system, run `ffplay -timeout 120000000 rtmp://127.0.0.1` to wait with a very long timeout
- Start the stream by running `composer sail run stream`

For development purposes, you may want to run `sail artisan app:start-broadcast` instead, this will block until the stream ends (either by Ctrl + C or by the receiving RTMP server cutting the connection).
