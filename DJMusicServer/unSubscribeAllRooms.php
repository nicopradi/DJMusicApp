<?php
	include_once("config.php");
	
	$json_manager = new JSONManager(file_get_contents('php://input'));
	
	$user_id = $json_manager->get("user_id");
	checkIsLogged($user_id);
	
	$db->requestII("SELECT id FROM rooms");
	while($room = $db->readII()){
		$room_id = $room['id'];
		
		if(isSubscribed($user_id, $room_id)){
			$db->request("SELECT turnInRoom FROM usersinrooms WHERE userId=:userId AND roomId=:roomId", array('userId' => $user_id, 'roomId' => $room_id));
			$response = $db->read();
			$currentTurn = $response['turnInRoom'];
			
			$db->delete(
				"usersinrooms", 
				array('userId' => $user_id, 'roomId' => $room_id)
			);
			
			$db->decrease("rooms", "listeningUsers", array('id' => $room_id));
			if($currentTurn >= 0){
				$db->decrease("rooms", "totalUsers", array('id' => $room_id));
				reassignTurns($currentTurn, $room_id);
			}
		} 
	}
	
	$json_manager->send();
	
	$db->writeLog("event", "user $user_id has unsubscribed from all rooms");
?>