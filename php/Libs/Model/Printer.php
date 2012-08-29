<?php
namespace Model;

use Database\DB as DB;
use Core\XML as XML;
use Core\Framework as Framework;

/**
 * @package Model
 */
class Printer {

	public static function CompareModelWithDatabase() {
		$title = 'Comparing the databases';
		$url = URL_ROOT;
		$xml_path = str_replace(DIR_ROOT, '', DIR_APP.'/model.xml');

		$db = IS_LIVE? config()->database->live: config()->database->dev;

		if (!$db) {
			$content = "<p style=\"font-size:20px;text-align:center\">Database not configured.</p>";
			return render('layout', compact('title', 'content', 'url'), URL_ROOT);
		}

		$db_name = "{$db->host}/{$db->name}";

		ob_start();

		if (!empty($_POST['sql'])) {
			print self::HandleSqlQuery($_POST['sql']);
		}
		
		self::PrintTablesMissingInDB();
		self::PrintTablesMissingInXML();
		self::PrintCompareTables();

		$content = ob_get_clean();
		return render('Model/Results', compact('content', 'db_name', 'xml_path'));
	}

	public static function HandleSqlQuery($sql) {
		try {
			DB::exec($sql);
			$error = DB::error();
			$error = $error[2];
		}
		catch (\Database\DBQueryException $e) {
			$error = $e->getMessage();
		}
		
		if ($error) {
			return self::output('Fail', "Error <em>{$error}</em> in query:", self::QueryForm($sql, 'Try again'));
		}
		else {
			return self::output('Pass', "Database query successful", "<pre style=\"padding:6px\">{$sql}</pre>");
		}
	}

	public static function QueryForm($sql, $button='Submit query') {
		$url = URL_CURRENT_FULL;
		return render('Model/QueryForm', compact('url','sql','button'));
	}

	public static function PrintTablesMissingInDB() {
		$xml = Framework::Model();
		$model = new XMLToSQL();

		$total = $missing = 0;
		foreach ($xml->table as $table) {
			$total++;
			$name = (string) $table['name'];
			if (DB::tableExists($name)) continue;
			$missing++;

			$form = self::QueryForm($model->TableSQL($table), 'Create it');
			print self::output('Fail', "Table <em>{$name}</em> is missing", $form);
		}

		$exist = $total - $missing;
		$type = $missing? 'Fail': 'Pass';
		$answer = $missing? 'No': 'Yes';
		$_t = $total == 1? 'table exists': 'tables exist';
		print self::output($type, "Do tables exist in database?", " - <em>{$answer}</em>, {$exist}/{$total} {$_t}.");
	}

	public static function TableExistsInXML($name) {
		$xml = Framework::Model();
		foreach ($xml->table as $table) {
			if ($name == (string) $table['name']) return true;
		}
		return false;
	}

	public static function PrintTablesMissingInXML() {
		$total = $missing = 0;

		foreach (DB::tables() as $name) {
			$total++;
			if (self::TableExistsInXML($name)) continue;
			$missing++;
			$table = DBToXML::TableAsXML($name);
			print self::output_sql("DROP TABLE `{$name}`;", "Table <em>{$name}</em> is missing from the XML file:", 'Drop the table', $table->asXML());
		}

		$exist = $total - $missing;
		$type = $missing? 'Fail': 'Pass';
		$answer = $missing? 'Yes': 'No';
		$_t = $total == 1? 'table exists': 'tables exist';
		print self::output($type, "Are there extra tables in database?", " - <em>{$answer}</em>, {$exist}/{$total} {$_t}.");
	}

	public static function column_diff_to_string($diff) {
		if (empty($diff)) return false;
		$out = array();
		foreach ($diff as $kk=>$vv) {
			$out[] = "{$kk}={$vv}";
		}
		return count($out)? join(', ', $out): '[none]';
	}


	public static function output($type, $title, $message='') {
		return "<div class=\"Test {$type}\"><b>{$title}</b>{$message}</div>";
	}

	public static function output_sql($sql, $title, $button='Execute SQL') {
		$message = self::QueryForm($sql, $button);
		return self::output('Fail', $title, $message);
	}

	public static function PrintCompareTables() {
		$one = Framework::Model();

		$tmp = DBToXML::AsXML(DB::tables())->asXML();
		$two = new Config($tmp);
		$two = $two->get();

		$xml = new XMLToSQL($one);

		$compare = new CompareXML($one, $two);

		$total = $incorrect = 0;

		$one_empty = !count($one->xpath('//table'));
		$two_empty = !count($two->xpath('//table'));

		if ($one_empty || $two_empty) return;
		
		foreach ($one->table as $table) {
			$table_name = (string) $table['name'];
			if (!DB::tableExists($table_name)) continue;

			$total++;
			$problem = false;

			list($indexes_missing_one, $columns_missing_two) = $compare->CompareColumns($table_name);

			if (count($indexes_missing_one)) {
				$problem = true;
				foreach ($indexes_missing_one as $column) {
					$field = $table->find("//field[@name='{$column}']");
					$sql = $xml->FieldSQL($field);
					$sql = "ALTER TABLE `{$table_name}` ADD {$sql};";
					print self::output_sql($sql, "Column <em>{$column}</em> is missing from the <em>{$table_name}</em> table.", 'Add it');
				}
			}

			if (count($columns_missing_two)) {
				$problem = true;
				foreach ($columns_missing_two as $column) {
					$sql = "ALTER TABLE `{$table_name}` DROP {$column};";
					print self::output_sql($sql, "Existing column <em>{$column}</em> is not present in the configuration for the <em>{$table_name}</em> table.", 'Remove it');
				}
			}

			// compare column attributes
			foreach ($table->field as $column) {
				$column_name = (string) $column['name'];
				list($diff_one, $diff_two) = $compare->CompareAttributes($table_name, $column_name);
				if (count($diff_one) || count($diff_two)) {
					$problem = true;
					$text = self::column_diff_to_string($diff_two).' vs '.self::column_diff_to_string($diff_one);
					$sql = "ALTER TABLE `{$table['name']}` CHANGE `{$column_name}` ".$xml->FieldSQL($column);
					//ALTER TABLE  `site_css_backup` CHANGE  `created`  `created` INT( 4 ) UNSIGNED NULL DEFAULT NULL
					print self::output_sql($sql, "Different column attributes in XML/{$table_name}.{$column_name}: {$text}", 'Change it');
				}
			}

			list($pri_one, $pri_two) = $compare->ComparePrimaryKey($table_name);
			if ($pri_one != $pri_two) {
				$problem = true;
				print self::output_sql("DROP TABLE `{$table_name}`;", "Table <em>{$table_name}</em> has incorrect primary key \"{$pri_two}\" instead of \"{$pri_one}\":", 'Drop the table');
			}

			// indexes
			list($indexes_missing_one, $indexes_missing_two) = $compare->CompareIndexes($table_name);
			if (count($indexes_missing_one) || count($indexes_missing_two)) $problem = true;
			self::PrintMissingDBIndexes($xml, $table_name, $indexes_missing_one);
			self::PrintMissingXMLIndexes($xml, $table_name, $indexes_missing_two);

			// unique indexes
			list($indexes_missing_one, $indexes_missing_two) = $compare->CompareUniques($table_name);
			if (count($indexes_missing_one) || count($indexes_missing_two)) $problem = true;
			self::PrintMissingDBUniques($xml, $table_name, $indexes_missing_one);
			self::PrintMissingXMLUniques($xml, $table_name, $indexes_missing_two);

			if ($problem) $incorrect++;
		}

		$correct = $total - $incorrect;
		$type = $incorrect? 'Fail': 'Pass';
		$answer = $incorrect? 'No': 'Yes';
		$_t = $total == 1? 'table is correct': 'tables are correct';
		$message = " - <em>{$answer}</em>, {$correct}/{$total} {$_t}.";
		print self::output($type, 'Are table definitions correct?', $message);
	}

	public static function PrintMissingDBIndexes($xml, $table, $indexes) {
		if (empty($indexes)) return false;
		foreach ($indexes as $name) {
			$sql = "ALTER TABLE `{$table}` ADD ".$xml->IndexSQL($name);
			print self::output_sql($sql, "Index <em>{$name}</em> is missing from the <em>{$table}</em> table.", 'Create it');
		}
	}

	public static function PrintMissingXMLIndexes($xml, $table, $indexes) {
		if (empty($indexes)) return false;
		foreach ($indexes as $name) {
			$sql = "ALTER TABLE `{$table}` DROP INDEX `{$name}`;";
			print self::output_sql($sql, "Existing index <em>{$name}</em> is not present in the configuration for the <em>{$table}</em> table.", 'Remove it');
		}

	}

	public static function PrintMissingDBUniques($xml, $table, $indexes) {
		if (empty($indexes)) return false;
		foreach ($indexes as $name) {
			$sql = "ALTER TABLE `{$table}` ADD ".$xml->IndexSQL($name, true);
			print self::output_sql($sql, "Unique index <em>{$name}</em> is missing from the <em>{$table}</em> table.", 'Create it');
		}
	}

	public static function PrintMissingXMLUniques($xml, $table, $indexes) {
		if (empty($indexes)) return false;
		foreach ($indexes as $name) {
			$sql = "ALTER TABLE `{$table}` DROP INDEX `{$name}`;";
			print self::output_sql($sql, "Existing unique index <em>{$name}</em> is not present in the configuration for the <em>{$table}</em> table.", 'Remove it');
		}

	}
}
