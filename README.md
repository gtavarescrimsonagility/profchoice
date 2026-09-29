# Professionals Choice

Monorepo for the Professionals Choice WordPress site.

## Theme

The `commercebuild-velocity` theme is mirrored on the orphan branch
[`commercebuild-velocity`](../../tree/commercebuild-velocity). Each push there
publishes a release zip named `commercebuild-velocity.<version>.zip`.

`blueprint.json` installs the theme straight from the release zip URL, which
pins the version.

## Playground

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/gtavarescrimsonagility/profchoice/main/blueprint.json

Or locally:

```bash
npx @wp-playground/cli server --blueprint=./blueprint.json
```

Both only work while the repository is public: release assets of a private
repository cannot be downloaded without authentication.
