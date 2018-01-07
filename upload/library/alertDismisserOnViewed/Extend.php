<?php

class alertDismisserOnViewed_Extend {
	public static function getExtensions(){
		return [
			['XenForo_ViewPublic_', true, 'alertDismisserOnViewed_Extend_XfView'],
		];
	}
	public static function callback($class, array &$extend){
		if(!isset($GLOBALS['alertDismisserOnViewed_ExtendinUse'])){
			$GLOBALS['alertDismisserOnViewed_ExtendinUse']=[];
		}
		$xtds = static::getExtensions();
		foreach($xtds as $xtd){
			$baseClass = $xtd[0];
			$isStartsWith = $xtd[1];
			$toExtend = $xtd[2];
			if(
			($isStartsWith || $class==$baseClass) &&
			strpos($class,$baseClass)===0 &&
			!in_array($toExtend, $extend) &&
			!in_array($toExtend, $GLOBALS['alertDismisserOnViewed_ExtendinUse'])
			){
				$extend[]=$toExtend;
				$GLOBALS['alertDismisserOnViewed_ExtendinUse'][]=$toExtend;
			}
		}
	}
}
