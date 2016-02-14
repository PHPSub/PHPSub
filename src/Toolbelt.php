<?php
/*
 * This file licensed under the MIT license.
 *
 * (c) Sylvain Mauduit <sylvain@mauduit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPSub;

use PHPSub\Exception\ConfigurationException;
use PHPSub\Plugin\Archive\ArchivePlugin;
use PHPSub\Plugin\Distribution\DistributionPlugin;
use PHPSub\Plugin\EmbeddedScript\EmbeddedScriptPlugin;
use PHPSub\Plugin\Plugin;
use PHPSub\Plugin\PluginAwareCommand;
use PHPSub\Plugin\Process\ProcessPlugin;
use PHPSub\Plugin\System\SystemPlugin;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Sylvain Mauduit <sylvain@mauduit.fr>
 */
class Toolbelt extends BaseApplication
{
    const ENV_BUILD = 'build';
    const ENV_DIST = 'dist';

    /** @var Plugin[] */
    private $plugins = [];
    /** @var string */
    private $shortName;
    /** @var string */
    private $envVarPrefix;
    /** @var array */
    private $configuration;
    /** @var string */
    private $env;
    /** @var string */
    private $rootDir;
    /** @var string */
    private $homeDir;
    /** @var string */
    private $cacheDir;

    /**
     * @param string $name      Toolbelt name
     * @param string $shortName Toolbelt short name
     * @param string $version   Toolbelt version
     */
    public function __construct($name, $shortName, $version = '@git-version@')
    {
        parent::__construct($name, $version);

        $this->env = self::ENV_BUILD;
        if (\Phar::running() !== '') {
            $this->env = self::ENV_DIST;
        }

        $this->shortName = $shortName;
        $this->rootDir   = $this->getRootDir();

        $this->addPlugin(new SystemPlugin());
        $this->addPlugin(new ProcessPlugin());
        $this->addPlugin(new EmbeddedScriptPlugin());

        if ($this->env === self::ENV_BUILD) {
            $this->addPlugin(new ArchivePlugin());
            $this->addPlugin(new DistributionPlugin());
        }
    }

    /**
     * @param Plugin $plugin
     */
    public final function addPlugin(Plugin $plugin)
    {
        $this->validatePlugin($plugin);

        $plugin->setToolbelt($this);
        $this->plugins[$plugin->getName()] = $plugin;
    }

    /**
     * @return array
     */
    public function getConfigPaths()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $configs = $this->loadConfiguration();

        foreach ($this->plugins as $plugin) {
            $pluginConfiguration = array_key_exists($plugin->getName(), $configs)
                ? $configs[$plugin->getName()]
                : [];

            try {
                $plugin->setConfiguration($pluginConfiguration);
            } catch (ExceptionInterface $e) {
                throw new ConfigurationException('Configuration error for plugin `'.$plugin->getName().'`', 0, $e);
            }

            $commands = $plugin->registerCommands();

            foreach ($commands as $command) {
                if ($command instanceof PluginAwareCommand) {
                    $command->setPlugin($plugin);
                }
            }

            $this->addCommands($commands);
        }

        foreach ($this->plugins as $plugin) {
            $plugin->configureExternalCommands($this->all());
        }

        return parent::doRun($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function getLongVersion()
    {
        if (('@' . 'git-version@') !== $this->getVersion()) {
            return sprintf(
                '<info>%s</info> version <comment>%s</comment> build <comment>%s</comment>',
                $this->getName(),
                $this->getVersion(),
                '@git-commit@'
            );
        }

        return '<info>' . $this->getName() . '</info> <comment>'.strtoupper($this->getEnv()).'</comment>';
    }

    /**
     * @param Plugin $plugin
     */
    private function validatePlugin(Plugin $plugin)
    {
        if (!preg_match("/^[a-z_]+$/", $plugin->getName())) {
            throw new \LogicException(sprintf('The plugin name `%s` is invalid.', $plugin->getName()));
        }

        if (array_key_exists($plugin->getName(), $this->plugins)) {
            throw new \LogicException(sprintf('The plugin name `%s` is already registered.', $plugin->getName()));
        }
    }

    /**
     * Load toolbelt configuration
     *
     * @return array
     * @throws ConfigurationException
     */
    private function loadConfiguration()
    {
        if (null === $this->configuration) {
            $configs = [];

            $mainToolbeltConfig = $this->loadMainToolbeltConfig();

            $configPaths = $this->getConfigPaths();
            foreach ($configPaths as $configPath) {
                if (!is_readable($configPath)) {
                    throw new \InvalidArgumentException(sprintf('The config path is not readable (%s)', $configPath));
                }
            }

            $configPaths = array_merge(
                [$mainToolbeltConfig['home'] . DIRECTORY_SEPARATOR . 'config.yml'],
                $configPaths
            );

            foreach ($configPaths as $configPath) {
                if (is_file($configPath)) {
                    $configs[] = Yaml::parse($configPath);
                }
            }

            $configs[] = [
                'toolbelt' => $mainToolbeltConfig
            ];

            $configs = array_reverse($configs);

            $this->configuration = array_merge_recursive(...$configs);

            $checkDirs = [
                'toolbelt.home'      => $this->configuration['toolbelt']['home'],
                'toolbelt.cache_dir' => $this->configuration['toolbelt']['cache_dir']
            ];

            foreach ($checkDirs as $confKey => $dir) {
                if (is_dir($dir) && !is_writable($dir)) {
                    throw new ConfigurationException(
                        sprintf('Configuration error for `%s`: Directory is not writable (%s)', $confKey, $dir), 0
                    );
                }

                if (!is_dir(dirname($dir)) || !is_writable(dirname($dir))) {
                    throw new ConfigurationException(
                        sprintf(
                            'Configuration error for `%s`: ' .
                            'Directory can\'t be created since parent dir is not writable (%s)',
                            $confKey,
                            $dir
                        ), 0
                    );
                }

                $this->createDir($dir);
            }

            $this->homeDir = $this->configuration['toolbelt']['home'] = realpath(
                $this->configuration['toolbelt']['home']
            );
            $this->cacheDir = $this->configuration['toolbelt']['cache_dir'] = realpath(
                $this->configuration['toolbelt']['cache_dir']
            );
        }

        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getShortName()
    {
        return $this->shortName;
    }

    /**
     * @return string
     */
    public function getEnvVarPrefix()
    {
        if (null === $this->envVarPrefix) {
            $this->envVarPrefix = 'TOOLBELT_' . strtoupper($this->getSecuredName());
        }

        return $this->envVarPrefix;
    }

    /**
     * @return string
     */
    public function getSecuredName()
    {
        $name = $this->getShortName();

        $name = str_replace(' ', '_', $name);
        $name = preg_replace('/[^A-Za-z0-9_]/', '', $name);

        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name);
        $name = trim($name, '_');

        return strtolower($name);
    }

    /**
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        if (null === $this->rootDir) {
            $r = new \ReflectionObject($this);
            $this->rootDir = dirname($r->getFileName());
        }

        return $this->rootDir;
    }

    /**
     * @return string
     */
    public function getHomeDir()
    {
        return $this->homeDir;
    }

    /**
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @param string $path
     *
     * @return string
     */
    public function normalizePath($path)
    {
        // Absolute path
        if (substr($path, 0, 1) === '/') {
            return $path;
        }

        return $this->getRootDir() . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @param string $name
     *
     * @return Plugin
     */
    public function getPlugin($name)
    {
        if (!isset($this->plugins[$name])) {
            throw new \InvalidArgumentException('Unknown plugin: ' . $name);
        }

        return $this->plugins[$name];
    }

    /**
     * @param Toolbelt $toolbelt
     *
     * @return string
     */
    protected function getDefaultHomeDir(Toolbelt $toolbelt)
    {
        $homeEnvVarKey = $toolbelt->getEnvVarPrefix() . '_HOME';
        $home = getenv($homeEnvVarKey);

        if (!$home) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if (!getenv('APPDATA')) {
                    throw new \RuntimeException(
                        sprintf(
                            'The APPDATA or %s environment variable must be set for toolbelt to run correctly',
                            $homeEnvVarKey
                        )
                    );
                }
                $home = strtr(getenv('APPDATA'), '\\', '/') . '/' . ucfirst($toolbelt->getSecuredName());
            } else {
                if (!getenv('HOME')) {
                    throw new \RuntimeException(
                        sprintf(
                            'The HOME or %s environment variable must be set for toolbelt to run correctly',
                            $homeEnvVarKey
                        )
                    );
                }
                $home = rtrim(getenv('HOME'), '/') . '/.' . $toolbelt->getSecuredName();
            }
        }

        return $home;
    }

    /**
     * @param string   $home
     * @param Toolbelt $toolbelt
     *
     * @return string
     */
    protected function getDefaultCacheDir($home, Toolbelt $toolbelt)
    {
        $configEnvVarKey = $toolbelt->getEnvVarPrefix() . '_CACHE_DIR';
        $cacheDir = getenv($configEnvVarKey);
        if (!$cacheDir) {
            if (defined('PHP_WINDOWS_VERSION_MAJOR')) {
                if ($cacheDir = getenv('LOCALAPPDATA')) {
                    $cacheDir .= '/' . ucfirst($toolbelt->getSecuredName());
                } else {
                    $cacheDir = $home . '/cache';
                }
                $cacheDir = strtr($cacheDir, '\\', '/');
            } else {
                $cacheDir = $home.'/cache';
            }
        }

        return $cacheDir;
    }

    /**
     * @param string $dir
     */
    private function createDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir);
        }
    }

    /**
     * @return array
     */
    private function loadMainToolbeltConfig()
    {
        $homeDir  = $this->getDefaultHomeDir($this);
        $cacheDir = $this->getDefaultCacheDir($homeDir, $this);

        return [
            'home' => $homeDir,
            'cache_dir' => $cacheDir,
        ];
    }
}
