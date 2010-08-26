<?php 

define('GLOBAL_CONSTANT', 'dsa')
$global_variable = 'lolazo';
$global_variable = 'lolazo';
$global_variable = 'lolazo';
echo $global_variable . $username . $password;



class Origin
{
	public    $myPublicVar;
	private   $myPrivateVar = 'Hello';
	protected $myProtectedVar;
	static    $myStaticVar;
	const ORIGIN_CONSTANT = 'HEY';
	public function    myOriginPublic($attr1, $attr2 = 'test')
	{
		echo "{$this_fucks_up_everything}";
	}
	private function   myOriginPrivate($attr3)
	{
		# code...
	}
	protected function myOriginProtected()
	{
		# code...
	}
	static function    myOriginStatic($value='')
	{
		# code...
	}
}


class Descendancy_First extends Origin
{
	public    $myPublicVar;
	private   $myPrivateVar = 'Hello';
	protected $myProtectedVar;
	static    $myStaticVar;
	const ODES_CONSTANT = 'THERE';
	public function    myDescendancyFirstPublic($attr1, $attr2 = 'test')
	{                               
		# code...                   
	}                               
	private function   myDescendancyFirstPrivate($attr3)
	{                               
		# code...                   
	}                               
	protected function myDescendancyFirstProtected()
	{                               
		# code...                   
	}                               
	static function    myDescendancyFirstStatic($value='')
	{
		# code...
	}
}

class Descendancy_Second extends Descendancy_First
{
	public    $myPublicVar;
	private   $myPrivateVar = 'Hello';
	protected $myProtectedVar;
	static    $myStaticVar;
	public function    myDescendancySecondPublic($attr1, $attr2 = 'test')
	{                              
		# code...                  
	}                              
	private function   myDescendancySecondPrivate($attr3)
	{                  
		# code...      
	}                  
	protected function myDescendancySecondProtected()
	{                  
		# code...      
	}                  
	static function    myDescendancySecondStatic($value='')
	{
		# code...
	}
}
