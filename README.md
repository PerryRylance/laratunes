# Laratunes
Laratunes is an open source streaming radio, RTMP client and web interface.

> ⚠️ If you intend to use this software to broadcast, please ensure that your stream complies with the policies of the platform you stream to and respects the intellectual property rights of the authors who's content you stream.

## Features
- Administrative interface for track management
- Audio fingerprinting for detecting duplicates on upload ~~and for users to find tracks by audio search~~ _(Coming soon!)_
- Streaming audio and a still image or video loop via RTMP (YouTube)
- ~~QR code link to track embedded in video~~ _(Coming soon!)_
- ~~Nightbot YouTube chat integration~~ _(Coming soon!)_
- ~~Voting system~~ _(Coming soon!)_
- Play count based shuffle ~~with weighting for upvoted tracks~~ _(Coming soon!)_
- ~~User suggested metadata~~ _(Coming soon!)_
- ~~User song requests~~ _(Coming soon!)_

## Deployment

### Requirements
- You will need a system that can run containers, for example a VPS, VM or Raspberry Pi with Docker installed
- You will need a domain
- You will need to either
    - Make sure that your system can be reached on port 443,
    - Or, alternatively you can use a CloudFlare tunnel

### Setup
- Clone this repository `git clone https://github.com/PerryRylance/laratunes && cd laratunes`
- Run `./install.sh` and follow the instructions

### Starting 
- Once you have completed setup, start the app with `./start.sh`

If you need to then you can also `./stop.sh` and `./restart.sh`.

### Serving

#### Via port 443
> TODO: Write up documentation here, Caddy / FrankenPHP should take care of everything

#### Via CloudFlare tunnel
The following assumes that you have purchased your domain, set up CloudFlare and set your domains nameservers to the given values from CloudFlare.

- Download CloudFlare's tunnel client, on a Pi 5 you can use `curl -LO https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-arm64.deb`
- Install the client with `sudo dpkg -i cloudflared-linux-arm64.deb`
- Check the installation with `cloudflared --version`
- Run `cloudflared tunnel login`
- Log in to CloudFlare on your host machine's browser, then visit the URL you obtained in the previous step
- Select the domain you want to tunnel into
- Back on your Pi, create the tunnel with `cloudflared tunnel create laratunes-tunnel`
- Create a `config.yml` (for example, at `~/.cloudflared/config.yml`) and open it with your favourite editor
- Inside your config file,  
    ```tunnel: <TUNNEL_ID_FROM_PREVIOUS_STEP>
    credentials-file: /home/pi/.cloudflared/<TUNNEL_ID_FROM_PREVIOUS_STEP>.json

    ingress:
    - hostname: <YOUR DOMAIN>
        service: http://127.0.0.1
    - service: http_status:404```
- On CloudFlare, navigate to the DNS settings for your domain
- Add a CNAME record with name `@` (or your chosen subdomain if using one), targetting `<TUNNEL_ID_FROM_PREVIOUS_STEP>.cfargotunnel.com`, proxied
- Start the tunnel with `cloudflared tunnel run laratunes-tunnel`
- Visit your domain, you should see the welcome page
- To install `cloudflared` as a service, first copy your files to root so that the root user can use them
    - `sudo mkdir -p /root/.cloudflared`
    - `sudo cp /home/pi/.cloudflared/* /root/.cloudflared/`
    - `sudo cloudflared service install`
- Lastly, __you need to configure FrankenPHP__ not to do https:// redirect as the tunnel will handle that, by seeing `SERVER_NAME=:80` in your `.env`
- If you started the app already you can `./restart.sh` to apply the change above

You should now be able to access your instance of Laratunes via your domain.

### Accessing the admin panels
Visit `/admin` on your domain to log in.

### Media discovery
Your stream cannot start until you have some tracks to play.

- If you already have audio in your media folder, use the media discovery tool through the UI. This can continue running after you close your browser or navigate away.
- Alternatively you can add tracks manually through "Tracks" in the navigation menu.

### Configuring the stream
There are two ways to configure your stream,

- Manually, go to YouTube, go live and obtain your stream URL and stream key, paste these into Laratunes' settings.
- ~~Automatically setup by visiting Settings and linking your admin account with your Google account and YouTube channel.~~ _(Coming soon!)_

## Usage
Ensure you have completed all the steps in the Deployment section above.

Once you have set in the required settings and discovered some tracks, please navigate `/admin/dashboard` on your domain and from there you will be prompted to click "Start Broadcast" to begin streaming.

## Development

### Installation
- Clone this repository
- Run `composer install`
- Run `sail npm install`

### Starting the containers
This project uses [Laravel Sail](https://laravel.com/docs/12.x/sail) to run in development.

`sail up -d`

If you intend to develop or run the browser tests then you should also run

`sail npm run dev`

### Testing
Tests can be run with `sail test`.

### Streaming locally
You can stream to `ffplay` locally to test your streams output.

- In your `.env` set `STREAM_URL` to `rtmp://host.docker.internal` (in quotes)
- Optionally, set a `MEDIA_PATH` into your `.env` (after changing this, you will need to `sail up -d --force-recreate` if you already started `sail`)
- Run `sail app:discover-media` to 
- On your host system, run `ffplay -timeout 120000000 rtmp://127.0.0.1` to wait with a very long timeout
- Start the stream by running `composer sail run stream`

For development purposes, you may want to run `sail artisan app:start-broadcast` instead, this will block until the stream ends (either by Ctrl + C or by the receiving RTMP server cutting the connection).
