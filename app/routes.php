<?php
// Routes

$app->get('/', App\Action\HomeAction::class)
    ->setName('homepage');

//тестовый метод "hello world"
$app->get('/hello/{name}', function ($request, $response, $args){
    return $response->write("Hello" . $args['name']);
});

//получить всех пользователей
$app->get('/users', function ($request, $response){
    $query = $this->db->prepare("SELECT * FROM User;");
    $query->execute();
    $todos = $query->fetchAll();
    print_r($todos);
    die;
    // return $response = $this->view->render($response, "home.twig");
});

//сохранить диалог

$app->post('/chat', function ($request, $response){
    $json_data = $request->getParsedBody();
    $visitor = $json_data['visitor'];
    $user = $json_data['user'];
    $messages = $json_data['messages'];

    try{
        //Проверка на наличие посетителя с таким id
        $query = $this->db->prepare("SELECT Count(visitor_id) FROM Visitor WHERE visitor_id = :id");
        $query->bindParam("id", $visitor['id']);
        $query->execute();
        $isVisitorExist = $query->fetchColumn(0);

        //если посетителя с таким id нет, добавляем в БД посетителя
        if ($isVisitorExist == 0) {
            $query = $this->db->prepare("INSERT INTO Visitor (visitor_id, visitor_name, email) VALUES (:id, :name, :email) ;");
            $query->bindParam("id", $visitor['id']);   //в БД надо поменять на uid
            $query->bindParam("name", $visitor['name']);
            $query->bindParam("email", $visitor['email']);
            $query->execute();
        }

        //сохраняем сообщения

        $query = $this->db->prepare("
          INSERT INTO VisitorMessage (date, text, visitor_id, user_id)
          VALUES (:date, :text, :visitor_id, :user_id);
        ");

        //VisitorMessage (date) - дата первого сообщения
        $dt = new DateTime($messages[0]['datetime']); //получаем дату первого сообщения и конвертим дату в DateTime
        $result_dt = $dt->format('Y-m-d H:i:s'); //затем конвертим в формат даты MySql
        $query->bindParam("date", $result_dt);
        $query->bindParam("visitor_id", $visitor['id']);
        $query->bindParam("user_id", $user['id']);
        $query->bindParam("text", json_encode($json_data));
        $query->execute();

        $json_data = "";
        $json_data['data']['id'] = $this->db->lastInsertId();

        return $response->withJson($json_data);
    }
    catch (Exception $e){
        $json_data ="";
        $json_data['error']['code'] = 404;
        $json_data['error']['message'] = "ID not found";
//        $json_data['error']['message'] = $e->getMessage(); //для себя
        return $response->withJson($json_data);
    }
});

$app->get('/chat', function($request, $response, $args){
    return $this->view->render($response, 'chat.twig');
});

$app->get('/chat/{filter}', function($request, $response, $args){
    //в запросе приходит фильтр, он определяет какой шаблон отправляется обратно

    return $this->view->render($response, $args['filter'] . '.twig');
});

$app->get('/login', function($request, $response, $args){
    return $this->view->render($response,  'login.twig');
});
