# Gutenberg Tailwind Safelist

Creates a safelist in your Tailwind config for all the Tailwind classes used in the Wordpress/Gutenberg editor. For use with [Sage 10](https://roots.io/sage).

## Requirements
- [Sage](https://github.com/roots/sage) >= 10.0

## Installation

Add this to the `repositories` section of your composer.json file:
```json
{
  "type": "vcs",
  "url": "https://github.com/broskees/gutenberg-tailwind-safelist.git"
}
```

Install via Composer:

```bash
$ composer require broskees/gutenberg-tailwind-safelist
```

## Usage

### Getting Started
Start by publishing the `config/tailwind.php` configuration file using Acorn.
```bash
$ wp acorn vendor:publish --provider="Broskees\GutenbergTwSafelist\GutenbergTwSafelistServiceProvider"
```

Next, create the database:

```bash
$ wp acorn updatetwdb
```

Finally add this code to your `tailwind.config.cjs` file:

On top:
```js
const
  fs = require('fs'),
  path = require('path'),
  Buffer = require('buffer').Buffer,
  classesString = fs.readFileSync(path.resolve(__dirname, 'gutenberg-classes.txt'), 'utf8') ?? '',
  classesBuffer = Buffer.from(classesString, 'base64') ?? false,
  classes = classesBuffer ? classesBuffer.toString().split(' ') : ''
```

After `content`:
```js
  safelist: classes,
```

**That's It!**

Your gutenberg tailwind classes will automatically be added to the `tailwind.config.cjs` file and `yarn build:prod` will be executed everytime a post is executed (if new classes are found).

## Todo
- Create packagist package
- Classes should be filtered for only tailwind classes with Regex, so only tailwind classes are added to the safelist (Help Wanted)
- Different filtering will eventually be put in place for different versions of Tailwind (Help Wanted)

## Bug Reports

If you discover a bug in Gutenberg Tailwind Safelist, please open an issue.

## Contributing

Contributing whether it be through PRs, reporting an issue, or suggesting an idea is encouraged and appreciated.

## License

Gutenberg Tailwind Safelist is provided under the [MIT License](./LICENSE.md).
