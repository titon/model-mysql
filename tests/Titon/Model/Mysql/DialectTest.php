<?php
/**
 * @copyright	Copyright 2010-2013, The Titon Project
 * @license		http://opensource.org/licenses/bsd-license.php
 * @link		http://titon.io
 */

namespace Titon\Model\Mysql;

use Titon\Model\Driver\Schema;
use Titon\Model\Query;
use Titon\Test\Stub\DriverStub;
use Titon\Test\Stub\Model\User;

/**
 * Test dialect SQL building.
 */
class DialectTest extends \Titon\Model\Driver\DialectTest {

	/**
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		parent::setUp();

		$this->driver = new DriverStub('default', []);
		$this->driver->connect();

		$this->object = new MysqlDialect($this->driver);
	}

	/**
	 * Test create table statement creation.
	 */
	public function testBuildCreateTable() {
			$schema = new Schema('foobar');
		$schema->addColumn('column', [
			'type' => 'int',
			'ai' => true
		]);

		$query = new Query(Query::CREATE_TABLE, new User());
		$query->schema($schema);

		$this->assertEquals("CREATE  TABLE IF NOT EXISTS `foobar` (\n`column` INT NOT NULL AUTO_INCREMENT\n);", $this->object->buildCreateTable($query));

		$schema->addColumn('column', [
			'type' => 'int',
			'ai' => true,
			'primary' => true
		]);

		$this->assertEquals("CREATE  TABLE IF NOT EXISTS `foobar` (\n`column` INT NOT NULL AUTO_INCREMENT,\nPRIMARY KEY (`column`)\n);", $this->object->buildCreateTable($query));

		$schema->addColumn('column2', [
			'type' => 'int',
			'null' => true,
			'index' => true
		]);

		$this->assertEquals("CREATE  TABLE IF NOT EXISTS `foobar` (\n`column` INT NOT NULL AUTO_INCREMENT,\n`column2` INT NULL,\nPRIMARY KEY (`column`),\nKEY `column2` (`column2`)\n);", $this->object->buildCreateTable($query));

		$schema->addOption('engine', 'InnoDB');

		$this->assertEquals("CREATE  TABLE IF NOT EXISTS `foobar` (\n`column` INT NOT NULL AUTO_INCREMENT,\n`column2` INT NULL,\nPRIMARY KEY (`column`),\nKEY `column2` (`column2`)\n) ENGINE=InnoDB;", $this->object->buildCreateTable($query));

		$schema = new Schema('foobar');
		$schema->addColumn('column', [
			'type' => 'int',
			'ai' => true
		]);

		$query = new Query(Query::CREATE_TABLE, new User());
		$query->schema($schema)->attribute('temporary', true);

		$this->assertEquals("CREATE TEMPORARY TABLE IF NOT EXISTS `foobar` (\n`column` INT NOT NULL AUTO_INCREMENT\n);", $this->object->buildCreateTable($query));

	}

	/**
	 * Test delete statement creation.
	 */
	public function testBuildDelete() {
		parent::testBuildDelete();

		$query = new Query(Query::DELETE, new User());
		$query->from('foobar')->attribute('quick', true);

		$this->assertRegExp('/DELETE\s+QUICK\s+FROM `foobar`;/', $this->object->buildDelete($query));

		$query->attribute('ignore', true);
		$this->assertRegExp('/DELETE\s+QUICK\s+IGNORE\s+FROM `foobar`;/', $this->object->buildDelete($query));
	}

	/**
	 * Test drop table statement creation.
	 */
	public function testBuildDropTable() {
		parent::testBuildDropTable();

		$query = new Query(Query::DROP_TABLE, new User());
		$query->from('foobar')->attribute('temporary', true);

		$this->assertRegExp('/DROP TEMPORARY TABLE IF EXISTS `foobar`;/', $this->object->buildDropTable($query));
	}

	/**
	 * Test insert statement creation.
	 */
	public function testBuildInsert() {
		parent::testBuildInsert();

		$query = new Query(Query::INSERT, new User());
		$query->from('foobar')->fields([
			'email' => 'email@domain.com',
			'website' => 'http://titon.io'
		]);

		$query->attribute('ignore', true);
		$this->assertRegExp('/INSERT\s+IGNORE\s+INTO `foobar` \(`email`, `website`\) VALUES \(\?, \?\);/', $this->object->buildInsert($query));

		$query->attribute('priority', 'highPriority');
		$this->assertRegExp('/INSERT HIGH_PRIORITY IGNORE INTO `foobar` \(`email`, `website`\) VALUES \(\?, \?\);/', $this->object->buildInsert($query));
	}

	/**
	 * Test select statement creation.
	 */
	public function testBuildSelect() {
		parent::testBuildSelect();

		$query = new Query(Query::SELECT, new User());
		$query->from('foobar')->attribute('distinct', true);

		$this->assertRegExp('/SELECT\s+DISTINCT\s+\* FROM `foobar`;/', $this->object->buildSelect($query));

		$query->attribute('distinct', 'all');
		$this->assertRegExp('/SELECT\s+ALL\s+\* FROM `foobar`;/', $this->object->buildSelect($query));

		$query->attribute('optimize', 'sqlBufferResult');
		$this->assertRegExp('/SELECT\s+ALL\s+SQL_BUFFER_RESULT\s+\* FROM `foobar`;/', $this->object->buildSelect($query));

		$query->attribute('cache', 'sqlCache');
		$this->assertRegExp('/SELECT\s+ALL\s+SQL_BUFFER_RESULT\s+SQL_CACHE\s+\* FROM `foobar`;/', $this->object->buildSelect($query));
	}

	/**
	 * Test update statement creation.
	 */
	public function testBuildUpdate() {
		parent::testBuildUpdate();

		$query = new Query(Query::UPDATE, new User());
		$query->from('foobar')->fields(['username' => 'miles'])->attribute('ignore', true);

		$this->assertRegExp('/UPDATE\s+IGNORE\s+`foobar`\s+SET `username` = \?;/', $this->object->buildUpdate($query));

		$query->attribute('priority', 'lowPriority');
		$this->assertRegExp('/UPDATE LOW_PRIORITY IGNORE `foobar`\s+SET `username` = \?;/', $this->object->buildUpdate($query));
	}

}