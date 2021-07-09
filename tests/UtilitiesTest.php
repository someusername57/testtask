<?php
namespace tests;

use PHPUnit\Framework\TestCase;
use KanbanBoard\Utilities;

class UtilitiesTest extends TestCase
{
    public static function setUpBeforeClass(){
        Utilities::init();
    }
    
    /**
     * @dataProvider providerEnvNames
    */
    public function testInit($envName){
        $this -> assertNotFalse(getenv($envName));
    }
    
    /**
     * @dataProvider providerEnvNames
     * @depends testInit
    */
    public function testEnv($envName){
        $this -> assertNotEmpty(Utilities::env($envName));
    }
    
    
    public function providerEnvNames(){
        return [
            ['GH_CLIENT_ID'],
            ['GH_CLIENT_SECRET'],
            ['GH_ACCOUNT'],
            ['GH_REPOSITORIES']
        ];
    }
    
}


