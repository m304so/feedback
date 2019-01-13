<?php

const DB_HOST = 'localhost';
const DB_NAME = 'test';
const DB_USER = 'root';
const DB_PASS = '';

$connectionString = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
$PDO = new PDO($connectionString, DB_USER, DB_PASS);

$errMsg = '';

$query = 'SELECT name, dt, text, email FROM feedback';
$STMT = $PDO->prepare($query);
$STMT->execute();
$feedbacks = $STMT->fetchAll();

function validate($param, $type, $min = 1) {
	global $errMsg;
	$result = false;
	switch($type) {
		case 'string':
			$param = trim(preg_replace('/[^ a-zа-яё\d]/ui', '', $param));
			if (mb_strlen($param) > 0) {
				$result = $param;
			} else {
				$errMsg = 'Некорректная строка';
			}
			break;
		case 'email':
			if (!filter_var($param, FILTER_VALIDATE_EMAIL)) {
				$errMsg = 'Некорректный email';
			} else {
				$result = $param;
			}
			break;
	}
	return $result;
}

function addFeedback($params) {
	global $PDO;

	$query = 'INSERT INTO feedback (name, email, text) VALUES (:name, :email, :feedback)';
	$STMT = $PDO->prepare($query);
	if ($STMT->execute($params)) {
		$query = 'SELECT name, dt, text, email FROM feedback WHERE id = ' . $PDO->lastInsertId();
		$STMT = $PDO->prepare($query);
		$STMT->execute();
		$feedback = $STMT->fetch();
		$feedback['dt'] = date('m.d.Y H:i:s', strtotime($feedback['dt']));
		return $feedback;
	}
	return false;
}

if (!empty($_POST)) {
	if (validate($_POST['name'], 'string') 
			&& validate($_POST['email'], 'email')
			&& validate($_POST['feedback'], 'string')) {
		$data = addFeedback($_POST);
		$result['data'] = $data;
	}
	if (!empty($errMsg)) {
		$result['status'] = 'error';
		$result['message'] = $errMsg;
	} else {
		$result['status'] = 'success';
	}
	echo json_encode($result);
	die();
}
?>
<!doctype html>

<html lang="ru">
<head>
	<meta charset="utf-8">
	<title>Арарэль - тестовое задание</title>
	<meta name="description" content="Тестовое задание на должность PHP программиста">
	<meta name="author" content="Medvedev Sergey, m304so@yandex.ru">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<style>
		* {
			font-family: Arial;
			font-size: 14px;
		}
		.form-control {
			display: block;
			width: 320px;
			margin: 5px 0;
		}
		textarea.form-control {
			width: 318px;
		}
		input[type=submit].form-control {
			width: 324px;
		}
		.feedback-panel {
			width: 324px;
			padding: 10px;
		}
		.feedback-panel.test {
			background-color: #cecece;
		}
		.feedback-panel.success {
			background-color: green;
		}
		.feedback-panel.error {
			background-color: red;
		}
		.error-message {
			color: white;
			text-align: center;
			font-weight: bold;
		}
		.feedbacks-row {
			margin: 15px 0;
		}
	</style>
</head>

<body>
	<div class="feedback-panel">
		<form id="feedback-form" action="/" method="post">
			<input class="form-control" type="text" name="name" placeholder="Имя" id="name">
			<input class="form-control" type="text" name="email" placeholder="Email" id="email">
			<textarea class="form-control" name="feedback" id="feedback"></textarea>
			<input class="form-control" type="submit" value="Отправить">
		</form>
	</div>
	<hr>
	<div class="feedbacks">
		<?php if (!empty($feedbacks)) : ?>
			<?php foreach($feedbacks as $feedback) { ?>
				<div class="feedbacks-row">
					<b><?php echo $feedback['name']; ?>:</b>
					[<?php 
						$timeStamp = date('m.d.Y H:i:s', strtotime($feedback['dt']));
						echo $timeStamp; 
					?>]<br>
					<?php echo $feedback['text']; ?><br>
					(<?php echo $feedback['email']; ?>)
				</div>
			<?php } ?>
		<?php endif; ?>
	</div>
</body>
</html>

<script>

	function clear() {
		$('.feedback-panel').removeClass('test');
		$('.feedback-panel').removeClass('error');
		$('.feedback-panel').removeClass('success');
	}
	$(function(){
		$('#feedback-form').submit(function(e){
			e.preventDefault();
			$('.feedback-panel').addClass('test');
			$('.error-message').remove();
			
			var $form = $(this);
			var data = $form.serialize();
			
			$.ajax({
				type: $form.attr('method'),
				url: $form.attr('action'),
				dataType: 'json',
				data: data,
			}).done(function(answer) {
				$('.feedback-panel').removeClass('test');
				console.log(answer);
				console.log(answer.data);
				if (answer.status == 'success') {
					clear();
					$('.feedback-panel').addClass('success');
					let text = '<b>' + answer.data['name'] + ':</b>'
							 + '[' + answer.data['dt'] + ']<br>'
							 + answer.data['text']
							 + '(' + answer.data['email'] + ')';
					$('.feedbacks').append('<div class="feedbacks-row">' + text + '</div>');
					setTimeout(function() {
						clear();
					}, 1000);
				} else {
					clear()
					$('.feedback-panel').addClass('error');
					$('.feedback-panel').append('<div class="error-message">' + answer.message + '</div>');
					console.log(answer);
				}
			}).fail(function() {
				clear()
				$('.feedback-panel').addClass('error');
			});
		});
	});
</script>