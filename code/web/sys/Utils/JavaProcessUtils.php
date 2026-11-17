<?php

/**
 * Builds the command lines used to start Aspen's Java processes (indexers, extractors, the reindexer and cron.jar)
 * and the pattern used to find them in the process list again.
 *
 * JVM options come from the [JavaProcesses] section of config.ini. defaultOptions applies to every process and an
 * entry named after the process (the jar name without .jar) is appended after it. The JVM uses the last occurrence of
 * an option, so a process specific value overrides the default one.
 *
 * This class has no dependencies so it can be used from scripts that do not load bootstrap.php.
 */
class JavaProcessUtils {
	/**
	 * Matches the start of a Java process command line: java (or a quoted java.exe on Windows), any JVM options and
	 * then -jar. The jar name follows. Free of literal whitespace and # so it can be used in patterns with the x flag.
	 */
	const JAVA_COMMAND_PATTERN = 'java(?:\.exe")?(?:\s+-(?!jar\s)\S+)*\s+-jar\s+';

	/**
	 * Returns the JVM options configured for a process.
	 *
	 * Options are separated by whitespace, so an individual option cannot contain spaces. Anything that does not look
	 * like a JVM option, or that would change which jar is run, is left out and returned in $ignoredOptions.
	 *
	 * @param array $configArray The merged config.ini settings
	 * @param string $processName The process name (the jar name without .jar)
	 * @param array|null $ignoredOptions Set to the options that were left out
	 * @return string[]
	 */
	public static function getJvmOptions(array $configArray, string $processName, ?array &$ignoredOptions = null): array {
		$ignoredOptions = [];
		$settings = $configArray['JavaProcesses'] ?? [];
		if (!is_array($settings)) {
			return [];
		}
		//enabled = false turns the options off and starts the processes with a plain java -jar, as before 26.12
		if (array_key_exists('enabled', $settings) && !filter_var($settings['enabled'], FILTER_VALIDATE_BOOLEAN)) {
			return [];
		}

		$rawOptions = [];
		foreach (['defaultOptions', $processName] as $key) {
			if (isset($settings[$key]) && is_string($settings[$key])) {
				$rawOptions = array_merge($rawOptions, preg_split('/\s+/', trim($settings[$key]), -1, PREG_SPLIT_NO_EMPTY));
			}
		}

		$jvmOptions = [];
		foreach ($rawOptions as $option) {
			if (self::isValidJvmOption($option)) {
				$jvmOptions[] = $option;
			} else {
				$ignoredOptions[] = $option;
			}
		}
		return $jvmOptions;
	}

	/**
	 * A JVM option must start with a dash. Options that select what gets run (-jar, -cp, -classpath, -m, --module and
	 * friends) are rejected because they would break both the launch and the process detection.
	 */
	public static function isValidJvmOption(string $option): bool {
		if (!preg_match('/^-[^\s]+$/', $option)) {
			return false;
		}
		$optionName = strtolower(explode('=', $option, 2)[0]);
		return !in_array($optionName, ['-jar', '-cp', '-classpath', '--class-path', '-m', '--module', '-p', '--module-path', '--source'], true);
	}

	/**
	 * Process names double as directory, jar and config key names.
	 */
	public static function isValidProcessName(string $processName): bool {
		return (bool)preg_match('/^[A-Za-z0-9_]+$/', $processName);
	}

	/**
	 * Builds the shell command that starts a Java process from its own directory.
	 *
	 * On Linux the shell is replaced by java (exec) so the process list shows the java command line, which is what
	 * JAVA_COMMAND_PATTERN looks for.
	 *
	 * @param string $processDirectory The directory containing the jar
	 * @param string $processName The process name (the jar name without .jar)
	 * @param string $serverName The site name, passed as the first argument
	 * @param string[] $arguments Any additional arguments for the process
	 * @param string[] $jvmOptions The JVM options, usually from getJvmOptions()
	 * @param bool $isWindows Whether to build a Windows command
	 * @return string
	 */
	public static function buildCommand(string $processDirectory, string $processName, string $serverName, array $arguments, array $jvmOptions, bool $isWindows = false): string {
		$javaArguments = array_merge($jvmOptions, ['-jar', "$processName.jar", $serverName], $arguments);
		if ($isWindows) {
			return "cd $processDirectory; java " . implode(' ', $javaArguments);
		}
		return 'cd ' . escapeshellarg($processDirectory) . ' && exec java ' . implode(' ', array_map('escapeshellarg', $javaArguments));
	}

	/**
	 * Loads the default config.ini and overrides it with the site's config.ini the same way readConfig() does,
	 * without the rest of the bootstrap.
	 *
	 * @param string $sitesDirectory The sites directory (containing default and the site)
	 * @param string $serverName The site name
	 * @return array|null null if the site's config.ini could not be read
	 */
	public static function loadConfig(string $sitesDirectory, string $serverName): ?array {
		$defaultConfig = @parse_ini_file("$sitesDirectory/default/conf/config.ini", true);
		$siteConfig = @parse_ini_file("$sitesDirectory/$serverName/conf/config.ini", true);
		if ($siteConfig === false) {
			return null;
		}
		return self::mergeConfig($defaultConfig === false ? [] : $defaultConfig, $siteConfig);
	}

	private static function mergeConfig(array $config, array $overrides): array {
		foreach ($overrides as $key => $value) {
			if (is_array($value)) {
				$config[$key] = self::mergeConfig(isset($config[$key]) && is_array($config[$key]) ? $config[$key] : [], $value);
			} else {
				$config[$key] = $value;
			}
		}
		return $config;
	}
}
