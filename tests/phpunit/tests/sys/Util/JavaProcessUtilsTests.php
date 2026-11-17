<?php
namespace sys\Util;

use JavaProcessUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class JavaProcessUtilsTests extends TestCase {
	private const SERVER_NAME = 'aspen.localhost';

	public function __construct(string $name) {
		parent::__construct($name);
		require_once __DIR__ . '/../../../../../code/web/sys/Utils/JavaProcessUtils.php';
	}

	private static function processRegex(): string {
		return '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\s' . JavaProcessUtils::JAVA_COMMAND_PATTERN . '(.*?)\.jar\s' . self::SERVER_NAME . '/ix';
	}

	private static function nightlyRegex(): string {
		return '/(\d+)\s+.*?\d{2}:\d{2}:\d{2}\s' . JavaProcessUtils::JAVA_COMMAND_PATTERN . 'reindexer\.jar\s' . self::SERVER_NAME . '\snightly/ix';
	}

	public function testGetJvmOptionsWithoutSection(): void {
		$this->assertSame([], JavaProcessUtils::getJvmOptions([], 'koha_export', $ignored));
		$this->assertSame([], $ignored);
	}

	public function testGetJvmOptionsAppendsProcessOptionsAfterDefaults(): void {
		$config = ['JavaProcesses' => [
			'defaultOptions' => ' -XX:+ExitOnOutOfMemoryError   -XX:MaxRAMPercentage=12 ',
			'koha_export' => '-XX:MaxRAMPercentage=25 -Xss1m',
		]];
		$this->assertSame(
			['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12', '-XX:MaxRAMPercentage=25', '-Xss1m'],
			JavaProcessUtils::getJvmOptions($config, 'koha_export')
		);
		$this->assertSame(
			['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12'],
			JavaProcessUtils::getJvmOptions($config, 'overdrive_extract')
		);
	}

	public function testGetJvmOptionsIgnoresInvalidOptions(): void {
		$config = ['JavaProcesses' => [
			'defaultOptions' => '-Xmx1g -jar other.jar -cp /tmp -classpath=/tmp --module-path=/tmp Xmx2g',
		]];
		$this->assertSame(['-Xmx1g'], JavaProcessUtils::getJvmOptions($config, 'cron', $ignored));
		$this->assertSame(['-jar', 'other.jar', '-cp', '/tmp', '-classpath=/tmp', '--module-path=/tmp', 'Xmx2g'], $ignored);
	}

	public function testBuildCommandLinux(): void {
		$this->assertSame(
			"cd '/usr/local/aspen-discovery/code/reindexer' && exec java '-XX:+ExitOnOutOfMemoryError' '-XX:MaxRAMPercentage=25' '-jar' 'reindexer.jar' 'aspen.localhost' 'nightly'",
			JavaProcessUtils::buildCommand('/usr/local/aspen-discovery/code/reindexer', 'reindexer', 'aspen.localhost', ['nightly'], ['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=25'])
		);
		$this->assertSame(
			"cd '/code/koha_export' && exec java '-jar' 'koha_export.jar' 'aspen.localhost'",
			JavaProcessUtils::buildCommand('/code/koha_export', 'koha_export', 'aspen.localhost', [], [])
		);
	}

	public function testBuildCommandWindows(): void {
		$this->assertSame(
			'cd c:\aspen\code\cron; java -Xmx1g -jar cron.jar aspen.localhost',
			JavaProcessUtils::buildCommand('c:\aspen\code\cron', 'cron', 'aspen.localhost', [], ['-Xmx1g'], true)
		);
	}

	public static function runningProcessProvider(): array {
		return [
			'no options' => ['aspen    1234     1  2 Nov15 ?        00:10:01 java -jar koha_export.jar aspen.localhost', '1234', 'koha_export'],
			'with options' => ['aspen    1234     1  2 Nov15 ?        00:10:01 java -XX:+ExitOnOutOfMemoryError -XX:MaxRAMPercentage=25 -jar koha_export.jar aspen.localhost', '1234', 'koha_export'],
			'option ending in jar' => ['aspen    42     1  0 10:00 ?        00:00:01 java -Dlog.file=a.jar -Xmx1g -jar user_list_indexer.jar aspen.localhost', '42', 'user_list_indexer'],
			'nightly reindexer' => ['aspen    77     1 99 02:00 ?        01:00:00 java -XX:MaxRAMPercentage=25 -jar reindexer.jar aspen.localhost nightly', '77', 'reindexer'],
		];
	}

	#[DataProvider('runningProcessProvider')]
	public function testProcessRegexFindsRunningProcesses(string $psLine, string $expectedPid, string $expectedName): void {
		$this->assertSame(1, preg_match(self::processRegex(), $psLine, $matches));
		$this->assertSame($expectedPid, $matches[1]);
		$this->assertSame($expectedName, $matches[2]);
	}

	public static function notAJavaProcessProvider(): array {
		return [
			'other site' => ['aspen    1234     1  2 Nov15 ?        00:10:01 java -Xmx1g -jar koha_export.jar other.localhost'],
			'cron wrapper' => ['aspen    55    50  0 02:00 ?        00:00:00 /bin/sh -c php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php aspen.localhost reindexer nightly'],
			'php launcher' => ['aspen    56    55  0 02:00 ?        00:00:00 php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php aspen.localhost reindexer nightly'],
			'grep' => ['aspen    99    98  0 02:00 pts/0    00:00:00 grep java'],
		];
	}

	#[DataProvider('notAJavaProcessProvider')]
	public function testProcessRegexIgnoresOtherProcesses(string $psLine): void {
		$this->assertSame(0, preg_match(self::processRegex(), $psLine));
		$this->assertSame(0, preg_match(self::nightlyRegex(), $psLine));
	}

	public function testNightlyRegexOnlyMatchesNightlyReindexer(): void {
		$this->assertSame(1, preg_match(self::nightlyRegex(), 'aspen    77     1 99 02:00 ?        01:00:00 java -XX:+ExitOnOutOfMemoryError -XX:MaxRAMPercentage=25 -jar reindexer.jar aspen.localhost nightly'));
		$this->assertSame(1, preg_match(self::nightlyRegex(), 'aspen    77     1 99 02:00 ?        01:00:00 java -jar reindexer.jar aspen.localhost nightly'));
		$this->assertSame(0, preg_match(self::nightlyRegex(), 'aspen    78     1 99 02:00 ?        01:00:00 java -XX:MaxRAMPercentage=25 -jar koha_export.jar aspen.localhost'));
	}

	public function testWindowsProcessRegex(): void {
		$regex = '/.*?' . JavaProcessUtils::JAVA_COMMAND_PATTERN . '(.*?)\.jar.*?\s+(\d+)/ix';
		$wmicLine = '"C:\Program Files\Java\bin\java.exe" -XX:MaxRAMPercentage=12 -jar overdrive_extract.jar aspen.localhost   4321';
		$this->assertSame(1, preg_match($regex, $wmicLine, $matches));
		$this->assertSame('overdrive_extract', $matches[1]);
		$this->assertSame('4321', $matches[2]);
	}

	public static function enabledProvider(): array {
		return [
			'not set' => [null, true],
			'true' => ['1', true],
			'false as parsed by parse_ini_file' => ['', false],
			'off' => ['off', false],
			'0' => ['0', false],
		];
	}

	#[DataProvider('enabledProvider')]
	public function testEnabled(?string $enabled, bool $expectOptions): void {
		$settings = ['defaultOptions' => '-Xmx1g'];
		if ($enabled !== null) {
			$settings['enabled'] = $enabled;
		}
		$this->assertSame($expectOptions ? ['-Xmx1g'] : [], JavaProcessUtils::getJvmOptions(['JavaProcesses' => $settings], 'cron'));
	}

	public static function cronLineProvider(): array {
		return [
			'linux template' => [
				"0 2 * * *     aspen   cd /usr/local/aspen-discovery/code/reindexer; java -jar reindexer.jar {sitename} nightly\n",
				"0 2 * * *     aspen   php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php {sitename} reindexer nightly\n",
			],
			'docker with redirection' => [
				"*/5 * * * * sudo -u www-data    cd /usr/local/aspen-discovery/code/oai_indexer && java -jar oai_indexer.jar {sitename} >/proc/1/fd/1 2>/proc/1/fd/2\n",
				"*/5 * * * * sudo -u www-data    php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php {sitename} oai_indexer >/proc/1/fd/1 2>/proc/1/fd/2\n",
			],
			'commented out' => [
				"#00 22 * * *   aspen   cd /usr/local/aspen-discovery/code/cron; java -jar cron.jar my.site\n",
				"#00 22 * * *   aspen   php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php my.site cron\n",
			],
			'already updated' => [
				"0 2 * * * aspen php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php my.site reindexer nightly\n",
				"0 2 * * * aspen php /usr/local/aspen-discovery/code/web/cron/runJavaProcess.php my.site reindexer nightly\n",
			],
			'custom options are left alone' => [
				"0 3 * * * aspen cd /usr/local/aspen-discovery/code/reindexer; java -Xmx4g -jar reindexer.jar my.site nightly\n",
				"0 3 * * * aspen cd /usr/local/aspen-discovery/code/reindexer; java -Xmx4g -jar reindexer.jar my.site nightly\n",
			],
			'other job' => [
				"*/5 * * * * aspen php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php my.site\n",
				"*/5 * * * * aspen php /usr/local/aspen-discovery/code/web/cron/checkBackgroundProcesses.php my.site\n",
			],
		];
	}

	#[DataProvider('cronLineProvider')]
	public function testRewriteCronLine(string $line, string $expected): void {
		$this->assertSame($expected, JavaProcessUtils::rewriteCronLine($line));
		//Rewriting again changes nothing
		$this->assertSame($expected, JavaProcessUtils::rewriteCronLine($expected));
	}

	public function testRewriteCronLineDoesNotJoinLines(): void {
		$crontab = "0 2 * * * aspen cd /a/code/oai_indexer; java -jar oai_indexer.jar my.site\n0 2 * * * aspen cd /a/code/reindexer; java -jar reindexer.jar my.site nightly\n";
		$this->assertSame(
			"0 2 * * * aspen php /a/code/web/cron/runJavaProcess.php my.site oai_indexer\n0 2 * * * aspen php /a/code/web/cron/runJavaProcess.php my.site reindexer nightly\n",
			JavaProcessUtils::rewriteCronLine($crontab)
		);
	}

	public function testIsCustomJavaCronLine(): void {
		$this->assertTrue(JavaProcessUtils::isCustomJavaCronLine("0 3 * * * aspen cd /a/code/reindexer; java -Xmx4g -jar reindexer.jar my.site nightly\n"));
		$this->assertFalse(JavaProcessUtils::isCustomJavaCronLine("#0 3 * * * aspen cd /a/code/reindexer; java -Xmx4g -jar reindexer.jar my.site nightly\n"));
		$this->assertFalse(JavaProcessUtils::isCustomJavaCronLine("0 2 * * * aspen cd /a/code/reindexer; java -jar reindexer.jar my.site nightly\n"));
		$this->assertFalse(JavaProcessUtils::isCustomJavaCronLine("0 2 * * * aspen php /a/code/web/cron/runJavaProcess.php my.site reindexer nightly\n"));
	}

	public function testGetOutputFile(): void {
		$baseLogPath = sys_get_temp_dir() . '/javaProcessUtilsLogs_' . uniqid();
		$config = ['Site' => ['baseLogPath' => $baseLogPath]];
		$this->assertNull(JavaProcessUtils::getOutputFile($config, 'my.site', 'koha_export'));
		mkdir("$baseLogPath/my.site/logs", 0777, true);
		try {
			$this->assertSame("$baseLogPath/my.site/logs/koha_export_output.log", JavaProcessUtils::getOutputFile($config, 'my.site', 'koha_export'));
		} finally {
			rmdir("$baseLogPath/my.site/logs");
			rmdir("$baseLogPath/my.site");
			rmdir($baseLogPath);
		}
	}

	public function testShippedDefaults(): void {
		$sitesDirectory = sys_get_temp_dir() . '/javaProcessUtilsTests_' . uniqid();
		mkdir("$sitesDirectory/default/conf", 0777, true);
		mkdir("$sitesDirectory/test.localhost/conf", 0777, true);
		copy(__DIR__ . '/../../../../../sites/default/conf/config.ini', "$sitesDirectory/default/conf/config.ini");
		file_put_contents("$sitesDirectory/test.localhost/conf/config.ini", "[JavaProcesses]\nkoha_export = \"-Xmx2g\"\n");
		try {
			$config = JavaProcessUtils::loadConfig($sitesDirectory, 'test.localhost');
			$this->assertNotNull($config);
			$this->assertSame(['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12', '-XX:MaxRAMPercentage=25'], JavaProcessUtils::getJvmOptions($config, 'reindexer'));
			$this->assertSame(['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12'], JavaProcessUtils::getJvmOptions($config, 'overdrive_extract'));
			$this->assertSame(['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12', '-XX:MaxRAMPercentage=5'], JavaProcessUtils::getJvmOptions($config, 'user_list_indexer'));
			//The site's config.ini replaces the default entry for the process
			$this->assertSame(['-XX:+ExitOnOutOfMemoryError', '-XX:MaxRAMPercentage=12', '-Xmx2g'], JavaProcessUtils::getJvmOptions($config, 'koha_export', $ignored));
			$this->assertSame([], $ignored);
			$this->assertNull(JavaProcessUtils::loadConfig($sitesDirectory, 'missing.localhost'));

			//enabled = false in the site's config.ini turns the options off
			file_put_contents("$sitesDirectory/test.localhost/conf/config.ini", "[JavaProcesses]\nenabled = false\n");
			$config = JavaProcessUtils::loadConfig($sitesDirectory, 'test.localhost');
			$this->assertSame([], JavaProcessUtils::getJvmOptions($config, 'reindexer'));
			$this->assertSame([], JavaProcessUtils::getJvmOptions($config, 'overdrive_extract'));
		} finally {
			unlink("$sitesDirectory/default/conf/config.ini");
			unlink("$sitesDirectory/test.localhost/conf/config.ini");
			rmdir("$sitesDirectory/default/conf");
			rmdir("$sitesDirectory/default");
			rmdir("$sitesDirectory/test.localhost/conf");
			rmdir("$sitesDirectory/test.localhost");
			rmdir($sitesDirectory);
		}
	}
}
