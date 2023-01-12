<?php //CODE BY HW

/* Yii2Framework Migrations File Generate. */
/* Time: 2023-01-13 00:13:00 */
/* Example: `php migrate_code.php table1 table2 table3` */

ini_set('display_errors', 'On');
ini_set('date.timezone', 'Asia/Shanghai');
error_reporting(E_ALL);

if (PHP_SAPI !== 'cli') {
    exit;
}

define('DB_HOST', 'mysql');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'lmkscrm');
define('DB_PORT', '3306');

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;port=%s', DB_HOST, DB_NAME, DB_PORT);
    $db = new PDO($dsn, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $db->exec('set names utf8mb4');

    if (count($argv) <= 1) {
        echo "Please enter table name.\n";
        exit;
    }

    $filename = sprintf('%s/__migrate_code.php', __DIR__);
    $handle = fopen($filename, 'w+');
    fwrite($handle, c(0, '<?php'));

    foreach ($argv as $key => $arg) {
        if ($key === 0) {
            continue;
        }

        $sql = "SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s' LIMIT 1";
        $sql = sprintf($sql, DB_NAME, addslashes($arg));
        $rows = $db->query($sql)->fetch();

        if (empty($rows)) {
            printf("Table not found; `%s`\n", $arg);
            continue;
        }

        $tableComment = $rows['TABLE_COMMENT'];

        $sql = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '%s' AND TABLE_NAME = '%s'";
        $sql = sprintf($sql, DB_NAME, addslashes($arg));
        $rows = $db->query($sql)->fetchAll();

        fwrite($handle, c(0, ''));
        fwrite($handle, c(0, sprintf('// Create Table: %s ========', $arg)));
        fwrite($handle, c(0, '{'));
        fwrite($handle, c(1, sprintf("\$this->createTable('{{%%%s}}', [", $arg)));

        foreach ($rows as $row) {
            if ($row['COLUMN_NAME'] == 'id' && $row['COLUMN_KEY'] == 'PRI') {
                $code = 'primaryKey()';

            } else {
                $code = dataType($row);

                if ($unsigned = unsigned($row)) {
                    $code .= '->' . $unsigned;
                }

                if ($defaultValue = defaultValue($row)) {
                    $code .= '->' . $defaultValue;
                }

                if ($comment = comment($row)) {
                    $code .= '->' . $comment;
                }
            }

            fwrite($handle, c(2, sprintf("'%s' => \$this->%s,", $row['COLUMN_NAME'], $code)));
        }

        fwrite($handle, c(1, ']);'));
        fwrite($handle, c(0, ''));

        $sql = sprintf("SHOW INDEX FROM `%s`;", addslashes($arg));
        $rows = $db->query($sql)->fetchAll();

        $keyMap = [];

        foreach ($rows as $row) {
            $keyMap[$row['Key_name']]['nonUnique'] = $row['Non_unique'];
            $keyMap[$row['Key_name']]['column'][$row['Seq_in_index']] = $row['Column_name'];
        }

        foreach ($keyMap as $idx => $item) {
            if ($idx === 'PRIMARY') {
                continue;
            }

            $column = sprintf("'%s'", implode("', '", $item['column']));

            if ($item['nonUnique']) {
                fwrite($handle, c(1, sprintf("\$this->createIndex('%s', '{{%%%s}}', [%s]);", $idx, $arg, $column)));
            } else {
                fwrite($handle, c(1, sprintf("\$this->createIndex('%s', '{{%%%s}}', [%s], true);", $idx, $arg, $column)));
            }
        }

        if (!empty($tableComment)) {
            fwrite($handle, c(0, ''));
            fwrite($handle, c(1, sprintf("\$this->addCommentOnTable('{{%%%s}}', '%s');", $arg, $tableComment)));
        }

        fwrite($handle, c(0, '}'));

        fwrite($handle, c(0, ''));
        fwrite($handle, c(0, '// Drop Table: ' . $arg));
        fwrite($handle, c(0, '{'));
        fwrite($handle, c(1, sprintf("\$this->dropTable('{{%%%s}}');", $arg)));
        fwrite($handle, c(0, '}'));
    }

    fclose($handle);
    echo "\nOK\n";
    exit;

} catch(Exception $e) {
    echo "程序异常:\n";
    echo $e;
}

function c($indent, $code) {
    return implode(array_pad([], $indent * 4, ' ')) . $code . "\n";
}

function comment($row) {
    if (!empty($row['COLUMN_COMMENT'])) {
        return sprintf("comment('%s')", $row['COLUMN_COMMENT']);
    }

    return '';
}

function defaultValue($row) {
    if (!is_null($row['COLUMN_DEFAULT'])) {
        return sprintf("defaultValue('%s')", $row['COLUMN_DEFAULT']);
    }

    return '';
}

function unsigned($row) {
    if (stripos($row['COLUMN_TYPE'], 'unsigned') !== false) {
        return 'unsigned()';
    }

    return '';
}

function dataType($row) {
    $code = '';

    switch ($row['DATA_TYPE']) {
        case 'varchar':
            $code .= sprintf('string(%d)', $row['CHARACTER_MAXIMUM_LENGTH']);
            break;
        case 'char':
            $code .= sprintf('char(%d)', $row['CHARACTER_MAXIMUM_LENGTH']);
            break;
        case 'int':
            $code .= sprintf('integer(%d)', $row['NUMERIC_PRECISION']);
            break;
        case 'tinyint':
            $code .= sprintf('tinyInteger(%d)', $row['NUMERIC_PRECISION']);
            break;
        case 'decimal':
            $code .= sprintf('decimal(%d)', $row['NUMERIC_PRECISION']);
            break;
        case 'date':
            $code .= 'date()';
            break;
        case 'datetime':
            $code .= 'dateTime()';
            break;
        case 'text':
            $code .= 'text()';
            break;
        case 'timestamp':
            $code .= 'timestamp()';
            break;
        case 'bigint':
            $code .= 'bigInteger()';
            break;
        default:
            $code .= 'string()';
            break;
    }

    return $code;
}
