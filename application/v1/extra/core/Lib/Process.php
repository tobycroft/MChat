<?php 

// 进程处理封装类
// 使用递归函数式封装
class Process {

	public static function create(array $opt = array()){
		if (empty($opt)) {
			return false;
		}

		$default_opt = array(
			'num' => 1 , 
			'is_main_process_wait' => true , 
			'wait_s' => 0 , 
			'wait_ns' => 0 , 
			'is_listen_signal' => false , 
			'main_func' => null , 
			'cp_func' => null
		);

		$pids 				  = array();
		$count 				  = 1;
		$num 				  = !isset($opt['num']) 					? $default_opt['num'] 					: (is_int($opt['num']) 					? $opt['num'] 					: $default_opt['num']);
		$is_main_process_wait = !isset($opt['is_main_process_wait'])	? $default_opt['is_main_process_wait']  : (is_bool($opt['is_main_process_wait']) ? $opt['is_main_process_wait']  : $default_opt['is_main_process_wait']);
		$is_listen_signal 	  = !isset($opt['is_listen_signal']) 		? $default_opt['is_listen_signal'] 		: (is_bool($opt['is_listen_signal']) 	? $opt['is_listen_signal'] 		: $default_opt['is_listen_signal']);
		$wait_s 			  = !isset($opt['wait_s']) 					? $default_opt['wait_s'] 				: (is_int($opt['wait_s']) 				? $opt['wait_s'] 				: $default_opt['wait_s']);
		$wait_ns 			  = !isset($opt['wait_ns']) 				? $default_opt['wait_ns'] 				: (is_int($opt['wait_ns']) 				? $opt['wait_ns'] 				: $default_opt['wait_ns']);
		$cp_func 			  = $opt['cp_func'];
		$main_func 			  = $opt['main_func'];

		// main function
		$create = function($cp_func = null , callable $main_func) use (&$create , &$count , $num , $is_main_process_wait , $is_listen_signal , $wait_s , $wait_ns , &$pids){
			$p = pcntl_fork();

			if ($p === -1) {
				exit('创建 ' . $count . ' 进程失败' . PHP_EOL);
			} else if ($p === 0) {
				$cp_func = func_bind_args(is_array($cp_func) ? $cp_func[$count - 1] : $cp_func, $count);

				$cp_func();
			} else {
				$pids[] = $p;

				if ($count === $num) {
					if (!$is_main_process_wait) {
						$main_func = func_bind_args($main_func);
					} else {
						if (!$is_listen_signal) {
							foreach ($pids as $pid)
								{
									pcntl_waitpid($pid , $status , WUNTRACED);
								}

							$main_func();
						} else {
							foreach ($pids as $pid)
								{
									pcntl_sigtimedwait(array(SIGCHLD) , $sig_info , $wait_s , $wait_ns);
									pcntl_waitpid($pid , $status , WUNTRACED | WNOHANG);
								}

							$main_func();
						}
					}
				} else {
					$count++;

					$create($cp_func , $main_func);
				}
			}
		};

		$create($cp_func , $main_func);

	}
}


$args = array(
	'num' => 3 , 
	'cp_func' => array(
		function(){
			echo 'hello ';
		} , 

		function(){
			echo 'sir ';
		} , 

		function(){
			echo '!' . PHP_EOL;
		}
	) ,

	'main_func' => function(){
		echo 'all process created!' . PHP_EOL;
	}
);

Process::create($args);

/*
 * 给函数绑定参数
 * @param   callable $func 待绑定参数的函数
 * @return  Closure
 */
function func_bind_args(callable $func = null){
	$args = func_get_args();

	array_shift($args);

	return function() use($func , $args){
		return call_user_func_array($func , $args);
	};
}
