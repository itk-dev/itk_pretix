# ITK Pretix

This Drupal 8 module creates a new field type that enables a link to
<https://pretix.eu/> to be made.

Through the Pretix API it is possible to add, change or remove Pretix
event date entries from the Drupal interface.

1. Add a new field of type `pretix date field type` to an entity.
2. Add a new field of type `pretix event settings` to the entity.
3. Add a pretix connection through the settings (local.settings.php).
4. Watch the magic happen.

## Configuration

Go to `/admin/config/itk_pretix/pretixconfig` and enter your pretix details.

A hidden configuration option, `pretix_event_slug_template`, controls how pretix
event slugs (short forms) are generated. The default value is `!nid` and `!nid`
will be replaced with the actual node id when creating a pretix event.

To change the value of `pretix_event_slug_template`, set it in your site's
settings, e.g. (in `settings.local.php`):

```php
$config['itk_pretix.pretixconfig']['pretix_event_slug_template'] = 'dev-local';
```

If the value of `pretix_event_slug_template` is not empty, but `!nid` does not
occur in the value, `-!nid` will be appended and the final template will be
`dev-local-!nid`.

### Drupal base URL

For development, you may want to use a custom base URL for your Drupal site (overriding the base URL Drupal uses). To do
this, set `$settings['itk_pretix']['drupal_base_url']` in your local settings, e.g.

``` php
# settings.local.php
$settings['itk_pretix']['drupal_base_url'] = 'http://example.com:8888';
```

## Exporters

This module exposes a number of event Data exporters that are run via the pretix
REST api
(cf. [https://docs.pretix.eu/en/latest/api/resources/exporters.html](https://docs.pretix.eu/en/latest/api/resources/exporters.html))

All exporters implement `Drupal\itk_pretix\Exporter\ExporterInterface` (by
extending `Drupal\itk_pretix\Exporter\AbstractExporter`) and are managed by
`Drupal\itk_pretix\Exporter\Manager` which takes care of displaying exporter
parameters forms and running exporters.

The available exporters for a node can be run from
`/itk_pretix/pretix/event/exporters/{node}` where `{node}` is the node id.

## Building assets

We use [Encore](https://symfony.com/doc/current/frontend/encore/installation.html) to build assets.

First, install tools and requirements:

```sh
docker compose run --rm node npm install
```

Build during development:

```sh
docker compose run --rm node npm run watch
```

Build for production:

```sh
docker compose run --rm node npm run build
```

## Coding standards

The code must follw the [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards)

Apply and check the coding standards by running

```sh
docker compose run --rm phpfpm composer install
docker compose run --rm phpfpm composer normalize
docker compose run --rm phpfpm composer coding-standards-apply
docker compose run --rm phpfpm composer coding-standards-check
```

### Assets

Apply and check the coding standards in assets by running

```sh
docker compose run --rm node npm install
docker compose run --rm node npm run coding-standards-apply
docker compose run --rm node npm run coding-standards-check
```

### Markdown

``` sh
docker compose run --rm markdownlint markdownlint '**/*.md' --fix
docker compose run --rm markdownlint markdownlint '**/*.md'
```
