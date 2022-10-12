<?php

/*
 * 事务 + 预处理语句方式
 */

namespace Core\Lib;

class Database {
	// 保存的 Db 实例
    protected $_instance       = null;
	// 数据库连接实例
	protected $_connect		= null;
	// 结果集格式化类型
    protected  $_fetchType		= \PDO::FETCH_ASSOC;
    
	function __construct($db_type = 'mysql' , $host = '127.0.0.1' , $db_name = '' , $username = '' , $password = '' , $is_persistent = false , $charset  =  'utf8'){
		$db_type	 	 = isset($db_type)		   ? $db_type       : 'mysql';
		$host			 = isset($host)		       ? $host		    : '127.0.0.1';
		$is_persistent   = is_bool($is_persistent) ? $is_persistent : false;
        $charset		 = isset($charset)		   ? $charset	    : 'utf8';

        $this->_connect = new \PDO($db_type . ":host=" . $host . ";dbname=" . $db_name , $username , $password , [
            \PDO::ATTR_PERSISTENT => $is_persistent ,

            // PDO 驱动比较特殊：即使在数据库配置文件中，已经设置了 utf8 字符集。
            // 其实际表现也会是 gbk 字符集
            // 因而需要在运行时指定 utf8 字符集
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'set names ' . $charset
        ]);

        // 设置错误时抛出异常
        if ($this->_connect->getAttribute(\PDO::ATTR_ERRMODE) !== \PDO::ERRMODE_EXCEPTION) {
            $this->_connect->setAttribute(\PDO::ATTR_ERRMODE , \PDO::ERRMODE_EXCEPTION);
        }
	}

	/*
	 * 获取链接对象（PDO对象）
	 */
	public function getConnection(){
		return $this->_connect;
	}

	/*
	 * 获取最后插入数据库的一条数据
	 */
	public  function lastInsertId($name = null){
		if (!is_null($name) && !is_string($name)) {
			throw new \Exception('参数 1 类型错误');
		}
		
		return $this->_connect->lastInsertId($name);
	}

	// 以事务 + 预处理语句方式运行 SQL 语句
	private  function _execByTransaction(array $sql = []){
		if (empty($sql)) {
			return true;
		}

		// 开始事务
		$this->_connect->beginTransaction();

		try {
			// 执行sql语句
			foreach ($sql as $v)
            {
                $keys   = array_keys($v);
                $_sql   = $v[$keys[0]];
                $params = $v[$keys[1]];

                $stmt = $this->_connect->prepare($_sql);

                foreach ($params as $k1 => $v1)
                {
                    $k1 = ltrim($k1 , ':');
                    $stmt->bindValue(":{$k1}" , $v1 , $this->type($v1));
                }

                $stmt->execute();
            }

			// 提交事务
			$this->_connect->commit();
		} catch (\Exception $excp) {
			// 失败时回滚
			$this->_connect->rollBack();
			// 重新抛出错误信息
			throw new \Exception($excp->getMessage());
		}
	}

    // 执行事务
    public  function transaction(callable $function){
        // 开始事务
        $this->_connect->beginTransaction();

        try {
            $res = call_user_func($function);

            $this->_connect->commit();

            return $res;
        } catch(\Exception $excep) {
            // 失败时回滚
            $this->_connect->rollBack();

            // 重新抛出错误信息
            throw new \Exception($excep->getMessage());
        }
    }

	// 格式化 PDO 返回的查询结果集
	public  function formatQRel(\PDOStatement $PDOStatement){
		return $PDOStatement->fetchAll($this->_fetchType);
	}

    /**
     * @param Mixed $v
     */
    public function type($v){
        if (is_string($v)) {
            return \PDO::PARAM_STR;
        }

        if (is_int($v) || is_float($v)) {
            return \PDO::PARAM_INT;
        }

        if (is_null($v)) {
            return \PDO::PARAM_NULL;
        }

        if (is_bool($v)) {
            return \PDO::PARAM_BOOL;
        }

        if (is_resource($v)) {
            return \PDO::PARAM_LOB;
        }

        throw new \Exception("MySQL 不支持的数据类型");
    }

	/*
	 * 用法1：直接执行原生的 sql 语句
	 * 用法2：预处理语句方式执行 sql 语句
	 * 第一种情况：$sql 是字符串
	 * 第二种情况：$sql 是数组，则须符合下面这种：
		$sql = array(
			// 原生语句
			'select * from person' => [] ,	
			// SQL 预处理语句第一种方式
			'select * from person where name = :nameValue and sex = :sexValue' => array(
				'nameValue' => 'chenxuelong' , 
				'sexValue'  => 'male'
			) , 
			// SQL 预处理语句第二种方式
			'select * from person where name = ? and sex = ?' => array('chenxuelong' , 'male')
		);

	 * @param  Array|String  $sql
	 * @param  Array         $params
	 * @param  Boolean       $transaction_mode
	 * @return QueryResult|Null
	 */
	public  function query($sql = '' , array $params = [] , $transaction_mode = false){
		$type_range		  = ['string' , 'array'];
		$sql_type		  = gettype($sql);
		$transaction_mode = is_bool($transaction_mode) ? $transaction_mode : false;

		if (array_search($sql_type , $type_range) === false) {
			throw new \Exception('参数 1 类型错误');
		}

		// 字符串时
		if (!$transaction_mode && is_string($sql)) {
			if (empty($params)) {
				// 直接执行 sql 语句
				return $this->_connect->query($sql);
			}
			
			$stmt = $this->_connect->prepare($sql);

			foreach ($params as $k => $v)
            {
                $k = ltrim($k , ':');
                $stmt->bindValue(":{$k}" , $v , $this->type($v));
            }

			if (!$stmt->execute()) {
				$err_msg = $stmt->errorInfo();
				throw new \Exception("执行SQL语句失败：" . $sql . "\r\nSQLState 码：" . $err_msg[0] . "\r\n驱动错误代码：" . $err_msg[1] . "\r\n错误字符串：" . $err_msg[2]);
			}

			return $stmt;	
		}

		// 包含正确格式的数据
		$sqls = [];

		// 包装成合适的格式
		if (is_array($sql)) {
            $sqls = $sql;
		} else {
            $sqls[] = [
				'sql'	 => $sql , 
				'params' => $params
			];
		}
		
		// 数组时
		$this->_execByTransaction($sqls);
	}

	/*
	 * 原生执行获取单行数据，若有多条数据，则只返回其中的第一条数据
	 * 若是获取的记录只有一个字段，则直接返回单元值
	 * 若是获取的记录不止一个字段，则返回整条记录

	 * @param  String $sql      待执行的 SQL 语句
	 * @param  Array  $params   如果是预处理 SQL 语句，则需提供参数
	 * @return Mixed
	 */ 
	public  function get($sql = '' , array $params = []){
		if (!is_string($sql)) {
			throw new \Exception('参数 1 类型错误');
		}

		$PDOStatement   = $this->query($sql , $params);
        $res            = $this->formatQRel($PDOStatement);
		
		// 无数据时
		if (empty($res)) {
			return false;
		}

		if (count($res) !== 1) {
			throw new \Exception('SQL 语句不合法（只允许返回一行记录）：' . $sql);
		}

		$res = $res[0];

        if (count($res) === 1) {
            $v = array_values($res);

            return $v[0];
        }

        return $res;
	}

	// 返回受影响的记录数
    public function rowCount(){
	    return $this->get('select row_count()');
    }

	/*
	 *原生执行获取所有记录
	 * @param  String $sql      待执行的 SQL 语句
	 * @param  Array  $params   如果是预处理 SQL 语句，则需提供参数
	 * @return Array 
	*/
	public  function getAll($sql = '' , array $params = []){
		if (!is_string($sql)) {
			throw new \Exception('参数 1 类型错误');
		}

		$PDOStatement   = $this->query($sql , $params);
		$result         = $this->formatQRel($PDOStatement);

		return $result;
	}
}
