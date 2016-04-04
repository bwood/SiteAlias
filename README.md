# sites-aliases-plugin
A plugin for Terminus which creates aliases for a given site name.

```
$ bin/terminus site aliases --site=example-site01
[2016-04-04 19:13:56] [info] Pantheon aliases file created

$ cat ~/.drush/onealias.aliases.drushrc.php
    $aliases['example-site01.live'] = array(
      'uri'              => 'live-example-site01.pantheonsite.io',
      'db-url'           => 'mysql://pantheon:1234@dbserver.live.1234.drush.in:12620/pantheon',
      'db-allows-remote' => true,
      'remote-host'      => 'appserver.live.1234.drush.in',
      'remote-user'      => 'live.1234',
      'ssh-options'      => '-p 2222 -o "AddressFamily inet"',
      'path-aliases'     => array(
        '%files'        => 'code/sites/default/files',
        '%drush-script' => 'drush',
      ),
    );
```

Clone this repository to your `~/terminus/plugins` directory, or wherever you have your
`TERMINUS_CONFIG_DIR/plugins` set. 

For more information on Terminus plugins, please [see the documentation](https://github.com/pantheon-systems/cli/wiki/Plugins).

## Motivation

`terminus sites alias` was failing with a gateway timeout. Needed the ability
to create just one alias.  

Pantheon just implemented "frozen" sites.  If a site is "frozen" the alias 
won't be included when `terminus sites aliases` is used.  This fixes our problem
and obviates the need for plugin.  At least for now.  In theory the problem 
could return if we exceed some number of unfrozen sites.

## TODO

* Since `terminus sites aliases` already exists (get's all you aliases) adding
`terminus site aliases` would be confusing.  Might be better to make it 
`terminus site get-aliases`.
* Consider adding `--env` to limit it to one environment.
* Presently it's only getting the live environment. Bug.
* Add option to append to pantheon.aliases.drushrc.php or to another file.
** Means you might have the same aliase twice in one file. First check the file
for the alias.