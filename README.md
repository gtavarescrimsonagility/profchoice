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

```bash
npx @wp-playground/cli server --blueprint=. --blueprint-may-read-adjacent-files
```

The repository is private, so the blueprint runs from a local checkout rather
than from a `playground.wordpress.net` link.
