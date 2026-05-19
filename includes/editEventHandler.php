<?php
require_once __DIR__ . '/api/bootstrap.php';
requireLogin();

$user = currentUser($mysqliConn);
$json = jsonInput();

$title = trim((string)($json['title'] ?? ''));
$date = trim((string)($json['date'] ?? ''));
$time = trim((string)($json['time'] ?? ''));
$eventId = (int)($json['event_id'] ?? 0);

if ($title === '' || $date === '' || $time === '' || $eventId <= 0) {
    respond([
        'success' => false,
        'message' => 'One of the fields is empty'
    ], 422);
}

$q = mysqli_prepare($mysqliConn, 'SELECT id, user_id, country_id FROM events WHERE id = ? LIMIT 1');
mysqli_stmt_bind_param($q, 'i', $eventId);
mysqli_stmt_execute($q);
$event = stmtFetchOneAssoc($q);
mysqli_stmt_close($q);

if (!$event) {
    respond(['success' => false, 'message' => 'Event not found'], 404);
}
if (!canEditEvent($user, ['user_id' => (int)$event['user_id'], 'country_id' => (int)$event['country_id']])) {
    respond(['success' => false, 'message' => 'Not allowed'], 403);
}

$stmt = mysqli_prepare($mysqliConn, 'UPDATE events SET date = ?, time = ?, title = ? WHERE id = ?');
if (!$stmt) {
    respond(['success' => false, 'message' => "couldn't update event"], 500);
}
mysqli_stmt_bind_param($stmt, 'sssi', $date, $time, $title, $eventId);
$ok = mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    respond(['success' => false, 'message' => "couldn't update event"], 500);
}

respond([
    'success' => true,
    'message' => 'Updated event'
]);







    // if(empty($title)&& empty($date)&&empty($time)){
    //     echo json_encode(array(
    //         "success" => false,
    //         "message" => "All Fields Empty"
    //     ));
    //     exit;
    // }
    // else if(!empty($title)&& empty($date)&&empty($time)){
    //     $sql ="UPDATE events set `title`='$title' WHERE id =$event_id";
        
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             'message' => 'Updated Title'
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update title"
    //         ));
    //         exit;
    //     }
        
    // }
    // else if(!empty($title)&& !empty($date)&&empty($time)){
    //     $sql ="UPDATE events set `title`='$title' `date`='$date' WHERE id=$event_id";
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             "message" => "Title and Date Updated"
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update title and date"
    //         ));
    //         exit;
    //     }
    // }
    // else if(!empty($title)&& !empty($date)&&!empty($time)){
    //     $sql ="UPDATE events set `title`='$title' `date`='$date' `time`='$time' WHERE id=$event_id";
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             "message" => "Updated All Fields"
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update all fields"
    //         ));
    //         exit;
    //     }
    // }
    // else if(empty($title)&& !empty($date)&&empty($time)){
    //     $sql ="UPDATE events set `date`='$date' WHERE id=$event_id";
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             "message" => "Updated Date"
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update date"
    //         ));
    //         exit;
    //     }
    // }
    // else if(empty($title)&& !empty($date)&&!empty($time)){
    //     $sql ="UPDATE events set `date`='$date' `time`='$time' WHERE id=$event_id";
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             "message" => "Updated date and time"
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update date and time"
    //         ));
    //         exit;
    //     }
    // }
    // else if(empty($title)&& empty($date)&&!empty($time)){
    //     $sql ="UPDATE events set `time`='$time' WHERE id=$event_id";
    //     if(mysqli_query($mysqliConn,$sql)){
    //         echo json_encode(array(
    //             "success" => true,
    //             "message" => "Updated Time"
    //         ));
    //         exit;
    //     }
    //     else{
    //         echo json_encode(array(
    //             "success" => false,
    //             "message" => "Couldn't update time"
    //         ));
    //         exit;
    //     }

    // }
    // else{
    //     echo json_encode(array(
    //         "success" => false,
    //         "message" => "unkown error"
    //     ));
    //     exit;;
    // }
?>
