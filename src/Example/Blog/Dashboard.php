<?php

class Example_Blog_Dashboard extends Mdogo_Controller_REST
{

  public function bootstrap(&$method, &$path)
  {

  	parent::bootstrap($method, $path);

  	$this->type = 'posts';

	}

	protected function read()
	{

		parent::read();
		
		return array( 'teste' => 123 );

	}	
	
}