# yt-dlp integration
We need to inteegration yt-dlp into this radio station so that users can add tracks directly from YouTube URL's.

Always write the tests first, then write the passing implementation afterwards.

## IP
Laratunes should not have yt-dlp installed by default.

Add a field in the settings page "yt-dlp version", defaulting to "Not installed", this should have a button "Install". Pressing "Install" makes a browser prompt dialogue telling the user that yt-dlp is to be used only on vidoes they have permission to do it on.

Confirming this should make laratunes install yt-dlp on the system. You can read instructions on that [here](https://github.com/yt-dlp/yt-dlp/wiki/Installation). Your call whether we use curl or apt, ideally do it into the container not into the hosts filesystem.

Once installed, the settings page should show the version and have an "upgrade" button, ideally we should do a remote check for the latest version so the user can see if they have it already and choose to uppgrade accordingly.

## Downloader
First make a YtDlpService to deal with this, we need to fake it for tests to test it's modes of failure as well as success.

If installed then the tracks index page should have an additional button, "Create from URL". This takes the user to an alternative Create track page which has a URL input. They paste in their YouTube URL there. Since we can't scrape metadata reliably this should also include fields for artist and track name, required.

Submitting invokes yt-dlp, this is a quick process so the output should be processed and sent back in the same request cycle. If successful we toast the user in the standard Filament way. If unsuccessful, please redirect the user back to the form, pre-filled URL field, and show them the reason why. This should also include a "Download log" link that holds the raw output, URL encoded in a download <a>.

This downloader must also integrate with the duplicate detection. The user should receive a warning after the track is downloaded that it is a possible duplicate.
