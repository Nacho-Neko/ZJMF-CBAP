<?php 
namespace server\pve;

use think\facade\Db;

class PVECloud{
	public function metaData(){
		return ['display_name'=>'PVE', 'version'=>'1.0.0'];
	}


    
    public function afterCreateFirstServer(){

    }



    public function afterDeleteLastServer(){

	}


    public function testConnect($params){


	}
}