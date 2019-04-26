<?require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php";

$APPLICATION->SetTitle('Создать задачу');?>
<?
// заполнения массива для вывода в поле статсус задачи
CModule::IncludeModule("iblock");
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/include/functions.php';
$id_iblock = CIBlock::GetList([], ['CODE' => 'CRM'])->fetch()['ID'];
$arr_for_status_tasks = array(); // масив что бы выодить статусы выполнения задачи
$arr_for_show_clints = array(); // масив что бы выодить клиентов
$arr_for_show_pulls = array(); // масив что бы выодить пулы
$element_first_in_array; // переменная для того что бы записать в него елемент массвиа
$element_property = array(); // массив для заполнения свойст елемента
$current_resp_and_manag = array(); // в этот массив записываем данные менеджера и ответсвенного при редактировании записи
($_GET['id_elem']) ? $id_elem = $_GET['id_elem'] : $id_elem = 'false';
// получение задачи
$treeSection = getTree($_GET['id_elem']);
$property_enums = CIBlockPropertyEnum::GetList(Array('sort' => 'desc'), Array("IBLOCK_ID" => $id_iblock, "CODE" => "STATUS", 'XML_ID' => array('STOP', 'WAIT')));
while ($items_enum = $property_enums->fetch()) {
	$arr_for_status_tasks[$items_enum['ID']] = $items_enum['VALUE'];
}
// получение клиентов и пулов
$db_list = $uf_arresult = CIBlockSection::GetList(Array("SORT" => "­­ASC"), Array("IBLOCK_ID" => $id_iblock, "DEPTH_LEVEL" => 1), false, array()); // получение всех клиентов
while ($items_clients = $db_list->fetch()) {
	$arr_for_show_clints[$items_clients['ID']] = $items_clients['NAME'];
}

$parent_id = array_shift(array_flip($arr_for_show_clints));
$db_list = GetIBlockSectionList($id_iblock, $parent_id, array('date' => 'asc'));

if ($treeSection) {
	$db_list = GetIBlockSectionList($id_iblock, $treeSection[1]['ID'], array('date' => 'asc'));
	$element_data = CIBlockElement::GetByID($_GET["id_elem"])->fetch();
	$property = CIBlockElement::GetProperty($id_iblock, $_GET["id_elem"], array("sort" => "asc"), Array());
	// формирования массива свойст для вставки в инпуты
	while ($element_property_not_valid = $property->fetch()) {
		// логика для записи в массивы менеджера и ответсвенного
		if ($element_property_not_valid['CODE'] == 'USER' || $element_property_not_valid['CODE'] == 'MANAGER') {
			$rsUser = CUser::GetByID($element_property_not_valid['VALUE'])->fetch();
			$id_user = $rsUser['ID']; // id пользователя
			$name = $rsUser['LAST_NAME'] . " " . $rsUser['NAME']; // имя пользователя
			$current_resp_and_manag[$element_property_not_valid['CODE']]['NAME'] = $name;
			$current_resp_and_manag[$element_property_not_valid['CODE']]['ID'] = $id_user;
		} else {
			$element_property[$element_property_not_valid['CODE']] = $element_property_not_valid['VALUE'];
		}

	}
}
while ($items_pull = $db_list->fetch()) {
	$arr_for_show_pulls[$items_pull['ID']] = $items_pull['NAME'];
}

if (!$arr_for_show_pulls) {
	$option_if_empty = '<option value="whitout_pull" id="create_pull">Без пулла</option><option value="create_pull" id="create_pull">Создать пулл</option>';
}

// получение клиентов и пулов конец

// получение записи если есть id

?>
<head>	<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
	<script type="text/javascript" src="/js/preshow.js"></script>

	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
	<style>
		#form_create_task{
			border: 2px solid white;
			padding: 10px;
			border-radius: 10px;
		}
		body{
			background-color: #f1f1f1f1;
		}
		.error{
			color: red;
		}
		.badge{
			cursor: pointer;
		}
		.create_pull{
			background-color: #f1f1f1f1;
		}
		.list-group-item{
			cursor: pointer;
		}
		.list-group-item:hover{
			background-color: #f1f1f1f1;
		}
		.whitout_pull{
			color:white;
		}

	</style></head>
	<div class="col-md-6 offset-md-3">
		<div class="row">
			<a href="https://<?=$_SERVER['SERVER_NAME']?>/crm/"><div class="col-md-6"><button type="submit" class="btn btn-default text-right" id="back_to_crm">Вернутся на главную</button></div></a>
			<div class="col-md-6"><h3 class="text-left">Создание задачи</h3></div>
		</div>
		<form action="/crm/" method='post' id="form_create_task" enctype="multipart/form-data">
			<div class="form-group">
				<label for="name_task">Название:</label>
				<input type="text" class="form-control" id="name_task" placeholder="Введите название задачи" name="NAME" value="<?=($element_data['NAME']) ? $element_data['NAME'] : ''?>">
			</div>
			<div class="form-group">
				<label for="description">Детальное описание:</label>
				<textarea class="form-control" id="description" placeholder="Введите детальное описание задачи" name="DESCRIPTION"><?=($element_data['DETAIL_TEXT']) ? $element_data['DETAIL_TEXT'] : ''?></textarea>
			</div>
			<div class="form-group">
				<label for="client" >Клиент:</label>
				<input type="text" class="form-control" id="client" placeholder="Введите клиента" name="client" data-id_client='<?=($treeSection[1]['ID']) ? $treeSection[1]['ID'] : ""?>' value='<?=($treeSection[1]['NAME']) ? $treeSection[1]['NAME'] : ""?>'>
				<ul class="list-group" id="ListKlients" class="lists"></ul>
			</div>
			<div class="form-group">
				<label for="pull" id='title_input_pull' >Пул:</label>
								<select class="form-control" id="pull" name='pull'>
					<?if (!$option_if_empty): ?>
					<option value="whitout_pull" id="create_pull">Без пулла</option>
					<option value="create_pull" id="create_pull">Создать пулл</option>
					<?foreach ($arr_for_show_pulls as $key => $value): ?>
					<?if ($treeSection && $key == $treeSection[2]['ID']): ?>
					<option value="<?=$key?>"  data-parent_section="<?=$parent_id?>" class="pull<?=$key?>" selected="selected"><?=$value?></option>
					<?else: ?>
					<option value="<?=$key?>"  data-parent_section="<?=$parent_id?>" class="pull<?=$key?>"><?=$value?></option>
					<?endif;?>
					<?endforeach;?>
					<?else:
	echo $option_if_empty;
	?>
																																		<?endif;?>
				</select><br>

			</div>
			<div class="form-group">
				<label for="plan_hour">План часов:</label>
				<input type="number" class="form-control" id="plan_hour" placeholder="Введите план часов" name="PLAN_HOURS" value="<?=($element_property['PLAN_HOURS']) ? $element_property['PLAN_HOURS'] : ''?>">
			</div>
			<div class="form-group">
				<label for="plan_minute">План минут:</label>
				<input type="number" class="form-control" id="plan_minute" placeholder="Введите план минут" name="PLAN_MINUTES" value="<?=($element_property['PLAN_MINUTES']) ? $element_property['PLAN_MINUTES'] : ''?>">
			</div>
			<div class="form-group">
				<label for="id_task">ID:</label>
				<input type="text" class="form-control" id="id_task" placeholder="Введите id"  name="ID_V_24" value="<?=($element_property['ID_V_24']) ? $element_property['ID_V_24'] : ''?>">
			</div>
			<div class="form-group">
				<label for="manager">Менеджер:</label>

				<input type="text" class="form-control" id="manager" placeholder="Введите менеджера" name="MANAGER" data-id_user_in_input_manag="<?=($current_resp_and_manag['MANAGER']) ? $current_resp_and_manag['MANAGER']['ID'] : ''?>" value="<?=($current_resp_and_manag['MANAGER']) ? $current_resp_and_manag['MANAGER']['NAME'] : ''?>">
				<ul class="list-group" id="myListManag" class="lists"></ul>
			</div>
			<div class="form-group">
				<label for="responsible">Ответственный:</label>
				<input type="text" class="form-control" id="responsible" placeholder="Введите ответственного" name="USER" data-id_user_in_input_resp="<?=($current_resp_and_manag['USER']) ? $current_resp_and_manag['USER']['ID'] : ''?>" value="<?=($current_resp_and_manag['USER']) ? $current_resp_and_manag['USER']['NAME'] : ''?>">
				<ul class="list-group" id="myListResponsible" class="lists"></ul>
			</div>
			<div class="form-group">
				<label for="deadline">Дедлайн:</label>
				<input type="text" class="form-control" id="deadline" placeholder="Дедлайн" name="DEADLINE" value="<?=($element_property['DEADLINE']) ? $element_property['DEADLINE'] : ''?>">
			</div>
			<hr>

			<div class="row">
				<div class="col-md-6">
					<div class="form-group">
						<label for="add_file" id="add_files">Добавить файлы:</label>
						<input type="file" class="form-control" id="files_photo" multiple>

						<?

$src = CFile::GetPath($element_property['FILES']);

$arFileTmp = CAllFile::ResizeImage(
	$arFile, // путь к изображению, сюда же будет записан уменьшенный файл
	array(
		"width" => 250, // новая ширина
		"height" => 150, // новая высота
	),
	BX_RESIZE_IMAGE_EXACT// метод масштабирования. обрезать прямоугольник без учета пропорций
);
?>
<div  class="center">
	<div class="image" style="background-image: url(<?=$src?>)">

	</div>
</div>
</div>

</div>
<div class="col-md-6">
	<div class="form-group">
		<label for="sel1">Оплачена:</label>
		<select class="form-control" id="payed" name="PAID">
			<option value="no" >Нет</option>
			<option value="11" <?=($element_property['PAID']) ? "selected='selected'" : " "?>>Да</option>
		</select>
	</div>
</div>
</div>
<hr>
<div class="col-md-12">
	<div class="form-group">
		<label for="sel1">Статус задачи:</label>
		<select class="form-control" id="ststus_task" name="STATUS">
			<?foreach ($arr_for_status_tasks as $key => $value): ?>
			<?if ($key == $element_property['STATUS']): ?>
			<option value="<?=$key?>" class="status_option<?=$key?>" selected="selected"><?=$value?></option>
			<?else: ?>
			<option value="<?=$key?>" class="status_option<?=$key?>"><?=$value?></option>
			<?endif;?>
			<?endforeach;?>
		</select>
	</div>
</div>
<br>
<button type="button" class="btn btn-default save_tasks" <?=($treeSection) ? 'data-action="update_tasks"' : 'data-action="save_tasks"'?>><?=($treeSection) ? 'Обновить задачу' : 'Сохранить задачу'?></button>
</form>

</div>
<div class="row">
	<div class="col-md-12">
		<div class="img-responsive" >

		</div>
	</div>
</div>

<script>
	$(document).ready(function(){
		var id_user_manag;
		var id_user_resp;
		$(function() {
			$('input[name="DEADLINE"]').daterangepicker({
				singleDatePicker: true,
				showDropdowns: true,
				minYear: 1901,
				maxYear: parseInt(moment().format('YYYY'),10),
				locale: {
					format: 'DD.MM.YYYY'
				}
			});
		});
///////получить пулы
$( "#client" ).change(function() {
	var section_code = $('#client option').filter(':selected').val();

});
//создание пула конец

// поиск менеджера
$('#manager').keyup(function(){
	if($('#manager').val()==''){
		// для того что бы не добавлялись юзеры если пустое поле
		$('#manager').attr('data-id_user_in_input_manag', 'false');
	}
	$.ajax({
		url: '/ajx/functions_for_form.php',
		method: 'post',
		data:{
			action: 'get_menager',
			name: $('#manager').val()
		},
		success: function(data){
			if(data!=''){
				$('#myListManag').show();
				$('#myListManag').html(data);
			}else{
				$('#myListManag').html('<div class="alert alert-danger" role="alert">Пользователь не найден</div>');
			}
		}
	});
});

$('body').on('click', '.menag', function(){
	$('#manager').val($(this).text());
	var id_manag = $(this).data('user_id_manag');
	$('#manager').attr('data-id_user_in_input_manag', id_manag);
	id_user_manag = $('#manager').attr('data-id_user_in_input_manag');
	$('#myListManag').hide();
});
// поиск менеджера конец
//поиск ответственного
$('#responsible').keyup(function(){
	if($('#responsible').val()==''){
			// для того что бы не добавлялись юзеры если пустое поле
			$('#responsible').attr('data-id_user_in_input_resp', 'false');
		}
		$.ajax({
			url: '/ajx/functions_for_form.php',
			method: 'post',
			data:{
				action: 'get_responsible',
				name: $('#responsible').val()
			},
			success: function(data){
				if(data!=''){
					$('#myListResponsible').show();
					$('#myListResponsible').html(data);
				}else{
					$('#myListResponsible').html('<div class="alert alert-danger" role="alert">Пользователь не найден</div>');
				}

			}
		});
	});
$('body').on('click', '.resp', function(){
	$('#responsible').val($(this).text());
	var id_resp = $(this).data('id_user_resp');
	$('#responsible').attr('data-id_user_in_input_resp', id_resp);
	id_user_resp = $('#responsible').attr('data-id_user_in_input_resp');
	$('#myListResponsible').hide();
});
//поиск ответственного конец
// сохраниение задачи
$('.save_tasks').on('click', function(){
	if($('.save_tasks').data('action')==='update_tasks'){
		action = 'update_tasks';
		id_element = "<?=$id_elem?>";
	}
	else if ($('.save_tasks').data('action')==='save_tasks'){
		action = 'save_tasks';
		id_element = "<?=$id_elem?>";

	}
	var file_data = $('#files_photo').prop('files');
	var form_file = new FormData();
	var count = file_data.length;
	var files = [];
	for(x = 0; x<=count; x++){
		if(file_data[x]!=undefined){
			form_file.append('file'+x, file_data[x]);

		}
	}

	form_file.append('file', files);
	var form_data = $('#form_create_task').serialize();
	form_file.append('data_forms', form_data);
	form_file.append('action', action);
	id_user_manag = $('#manager').attr('data-id_user_in_input_manag');
	id_user_resp = $('#responsible').attr('data-id_user_in_input_resp');
	id_client = $("#client").attr('data-id_client');
	form_file.append('id_resp', id_user_resp);
	form_file.append('id_manag', id_user_manag);
	form_file.append('client', id_client);
	if(id_element){
		form_file.append('id_elem', id_element);
	}
	$.ajax({
		url: '/ajx/functions_for_form.php',
		cache: false,
		contentType: false,
		processData: false,
		data: form_data,
		method: 'post',
		dataType: 'text',
		data: form_file,
		success: function(data){
			console.log(data);
			if(data==''){
				alert('Не верно заполнена форма');
			}else{
				$('input').val('');
				var url = "http://<?=$_SERVER['SERVER_NAME']?>/crm/";
				$(location).attr('href',url);
			}
		},

	});
});
// сохраниение задачи конец

// закрытие выпадающего списка по клику в любом месте окна
$(document).on('click', function(){
	$('#myListManag').hide();
	$('#myListResponsible').hide();
	$('#ListKlients').hide();
});
// закрытие выпадающего списка по клику в любом месте окна конец

// открытие списика по клику на инпут менеджеры
$('#manager').on('click', function(){
	if($('#manager').val()==''){
		// для того что бы не добавлялись юзеры если пустое поле
		$('#manager').removeData('id_user_in_input_manag', 'false');
		$.ajax({
			url: '/ajx/functions_for_form.php',
			method: 'post',
			data:{
				action: 'get_menager',
				name: $('#manager').val()
			},
			success: function(data){
				$('#myListManag').show();
				$('#myListManag').html(data);


			}
		});
	}

});
// открытие списика по клику на инпут работяги конец
$('#responsible').on('click', function(){
	if($('#responsible').val()==''){
			// для того что бы не добавлялись юзеры если пустое поле
			$('#responsible').attr('data-id_user_in_input_resp', 'false');

			$.ajax({
				url: '/ajx/functions_for_form.php',
				method: 'post',
				data:{
					action: 'get_responsible',
					name: $('#responsible').val()
				},
				success: function(data){
					$('#myListResponsible').show();
					$('#myListResponsible').html(data);

				}
			});
		}
	});
// открытие списика по клику на инпут работяги конец
// открытие списка по клику клиенты
$('#client').on('click', function(){
	if($('#client').val()==''){
			// для того что бы не добавлялись юзеры если пустое поле
			$.ajax({
				url: '/ajx/functions_for_form.php',
				method: 'post',
				data:{
					action: 'get_all_clients',
					name: $('#client').val()
				},
				success: function(data){
					$('#ListKlients').show();
					$('#ListKlients').html(data);

				}
			});
		}
	});
// открытие списка по клику клиенты конец
$('body').on('click', '.items_client',function(){
	section_code = $(this).val();
	$('#client').val($(this).text());
	$('#client').attr('data-id_client', $(this).val());
	$('#ListKlients').hide();
	$.ajax({
		url: '/ajx/functions_for_form.php',
		method: 'post',
		data:{
			action: 'get_all_pull',
			section: section_code
		},
		success: function(data){
			console.log(data);
			$('#pull').html('<option value="whitout_pull" id="create_pull">Без пулла</option><option value="create_pull" id="create_pull">Создать пулл</option>'+data);
			$('.items_pull:nth-child(3)').attr('selected','selected');
		}
	});
});

$('#client').keyup(function(){
	$.ajax({
				url: '/ajx/functions_for_form.php',
				method: 'post',
				data:{
					action: 'get_all_clients',
					name: $('#client').val()
				},
				success: function(data){
					if(data!=''){
						$('#ListKlients').show();
						$('#ListKlients').html(data);
					}else
					$('#ListKlients').html('<div class="alert alert-danger" role="alert">Пользователь не найден</div>');


				}
			});
	});

$('#plan_minute').keyup(function(){
	if($(this).val() > 60){
		$(this).val(60);
	}
});
});
</script>
<?require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php";