# Professionals Choice

Monorepo for the Professionals Choice WordPress site.

## Theme

The `commercebuild-velocity` theme is mirrored on the orphan branch
[`commercebuild-velocity`](../../tree/commercebuild-velocity). Each push there
publishes a release zip named `commercebuild-velocity.<version>.zip`.

`blueprint.json` pins the theme version; download that release zip next to it:

```bash
gh release download commercebuild-velocity.0.12.77 -p '*.zip' --clobber
```

## Playground

https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/gtavarescrimsonagility/profchoice/main/blueprint.json

Or locally:

```bash
npx @wp-playground/cli server --blueprint=. --blueprint-may-read-adjacent-files
```

The `playground.wordpress.net` link only works while the repository is public
and the theme zip is reachable from the blueprint; until then, run it locally.
