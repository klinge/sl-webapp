<?php
class TestController{
  
    // database connection and table name
    private $conn;
    private $request;

    public function __construct($request) 
    {
        $this->request = $request;
    }

    public function hello(){
        echo "Hello World";
    }

    public function helloName(array $params){
        echo "Hello, " . $params['name'];
    }
}
  