## Installation and configuration

### <a name="config-files">Config files</a>

An OEM installation supports multiple sites running within the same file structure. The default app is called Site and its files are in the `/php/App/Site` directory. Each installation has a main configuration file, `/php/config.xml`, as well as app-specific configuration files, which in the case of the Site app is located at `/php/App/Site/config.xml`. The contents of the main configuration file are overwriten by the contents of app-specific configuration file. For example, if the main configuration file contains:

```xml
<config>
  <site>
	<name>Default name</name>
	<url>www.example.com</url>
  </site>
</config>
```

...and the app-specific configuration file contains:

```xml
<config>
  <site>
	<name>App-specific name</name>
  </site>
  <rules>
	<max_users>10</max_users>
  </rules>
</config>
```

...then the resulting configuration object will contain:

```xml
<config>
  <site>
	<name>App-specific name</name>
	<url>www.example.com</url>
  </site>
  <rules>
	<max_users>10</max_users>
  </rules>
</config>
```

You should feel free to add your own content to the configuration files. I use them to make it easy for non-developers to modify application options such as page titles, copyright notices, game rules etc.

### <a name="database">Database configuration</a>

The database configuration is located in the main configuration file, `/php/config.xml`. By default it's commented out and the site can work just fine without it. The default database type is MySQL and the framework wasn't tested with any other database, although it's based on PDO, so should be relatively easy to use databases PDO supports.

In the standard installation OEM supports two database configurations -- LIVE and DEV. The correct configuration is provided by `Site::DatabaseConfiguration()` (inherited from `Core\Framework`) based on whatever `Site::IsLive()` returns -- if it returns true the `config->database->live` is used, and otherwise it's `config->database->dev`. You can customize this behavior by creating either of those methods in the `Site` class.

Note that due to the inheritance of configuration files, a database configuration placed in the app's `config.xml` file will override one in `/php/config.xml`.