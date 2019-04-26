<?require_once $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php";?>
<?
include_once $_SERVER['DOCUMENT_ROOT'] . '/include/AutoLoader.php';
CModule::IncludeModule("iblock");

// берем всех клиентов

$id_iblock = CIBlock::GetList([], ['CODE' => 'CRM'])->fetch()['ID'];
if ($_POST['action'] == 'get_all_clients') {

	$arFilter = Array('IBLOCK_ID' => $id_iblock, 'GLOBAL_ACTIVE' => 'Y', 'NAME' => '%' . $_POST['name'] . '%');
	$db_list = CIBlockSection::GetList(Array(), $arFilter, true);

	while ($x = $db_list->Fetch()) {
		if ($x['DEPTH_LEVEL'] == 1) {?>
			<li value="<?=$x['ID']?>" class="list-group-item items_client client<?=$x['ID']?>"><?=$x['NAME']?></li>
			<?}
	}
}

if ($_POST['action'] == 'get_all_pull') {
	var_dump($_POST['section']);
	$count = 0;
	$arFilter = Array('GLOBAL_ACTIVE' => 'Y');
	$id_iblock = CIBlock::GetList([], ['CODE' => 'CRM'])->fetch()['ID'];
	$db_list = GetIBlockSectionList($id_iblock, $_POST['section'], array('date' => 'asc'));

	while ($x = $db_list->Fetch()) {
		?>
			<option value="<?=$x['ID']?>" data-parent_section="<?=$_POST['section']?>" class='items_pull pull<?=$x["ID"]?>'><?=$x['NAME']?></option>
			<?}
}
// берем всех клиентов конец

// берем всех менеджеров
if ($_POST['action'] == 'get_menager') {
	$filter = array(
		"GROUPS_ID" => Array(7),
		'NAME' => '%' . $_POST["name"] . '%',
		array(
			"LOGIC" => "OR",
			array('LAST_NAME' => '%' . $_POST["name"] . '%'),

		));
	$rsUsers = CUser::GetList(($by = "ID"), ($order = "desc"), $filter);
	while ($arUser = $rsUsers->Fetch()) {?>
				<li class="list-group-item menag"  data-user_id_manag = "<?=$arUser['ID']?>"><?=$arUser['LAST_NAME']?> <?=$arUser['NAME']?></li>
				<?}
}
// берем всех менеджеров конец

// берем всех ответственных
if ($_POST['action'] == 'get_responsible') {
	$filter = array(
		"GROUPS_ID" => Array(6),
		'NAME' => '%' . $_POST["name"] . '%',
		array(
			"LOGIC" => "OR",
			array('LAST_NAME' => '%' . $_POST["name"] . '%'),

		));
	$rsUsers = CUser::GetList(($by = "ID"), ($order = "desc"), $filter);
	while ($arUser = $rsUsers->Fetch()) {?>
					<li class="list-group-item resp" data-id_user_resp = "<?=$arUser['ID']?>"><?=$arUser['LAST_NAME']?> <?=$arUser['NAME']?></li>
					<?}
}
// берем всех ответственных  конец

// сохраняем задачу
if ($_POST['action'] == 'save_tasks') {
	$searcharray = array();
	parse_str($_POST['data_forms'], $searcharray);
	$el = new CIBlockElement;
	$PROP = array();
	if (!$_POST['id_manag'] || !$_POST['id_resp'] || !$searcharray['NAME'] || !$searcharray['DESCRIPTION']) {
		return false;
	}
	if (!$searcharray['PLAN_HOURS'] && !$searcharray['PLAN_MINUTES']) {
		return false;
	}
	// получаем свойства
	$PROP = getProperies($searcharray);
	if ($searcharray['pull'] == 'create_pull') {
		$cout_sections = CIBlockSection::GetCount(array("SECTION_ID" => $_POST['client'])); // кол-во пулов
		$cout_sections += 1; // прибавляем один для генерации нового пула
		$name_pull = 'ПУЛ_' . $cout_sections; // создаем пулл
		$id_sect = createPull($_POST['client'], $id_iblock, $name_pull . time(), $name_pull, 500, false, $name_pull);
		echo createTask($USER->GetID(), $id_sect, $id_iblock, $PROP, $searcharray);

	} else if ($searcharray['pull'] == 'whitout_pull') {
		echo createTask($USER->GetID(), $id_sect, $id_iblock, $PROP, $searcharray);

	} else {
		echo createTask($USER->GetID(), $searcharray['pull'], $id_iblock, $PROP, $searcharray);
	}
}
// сохраняем задачу конец

// обновялем запись
if ($_POST['action'] == 'update_tasks') {

	$searcharray = array();
	parse_str($_POST['data_forms'], $searcharray);
	$el = new CIBlockElement;
	$PROP = array();
	if (!$_POST['id_manag'] || !$_POST['id_resp'] || !$searcharray['NAME'] || !$searcharray['DESCRIPTION']) {
		return false;
	}
	if (!$searcharray['PLAN_HOURS'] && !$searcharray['PLAN_MINUTES']) {
		return false;
	}
	// получаем свойства
	$PROP = getProperies($searcharray);
	var_dump($PROP);
	if ($searcharray['pull'] == 'create_pull') {
		$cout_sections = CIBlockSection::GetCount(array("SECTION_ID" => $_POST['client'])); // кол-во пулов
		$cout_sections += 1; // прибавляем один для генерации нового пула
		$name_pull = 'ПУЛ_' . $cout_sections; // создаем пулл
		$id_sect = createPull($_POST['client'], $id_iblock, $name_pull . time(), $name_pull, 500, false, $name_pull);
		echo createTask($USER->GetID(), $id_sect, $id_iblock, $PROP, $searcharray);

	} else if ($searcharray['pull'] == 'whitout_pull') {
		echo createTask($USER->GetID(), $_POST['client'], $id_iblock, $PROP, $searcharray);

	} else {
		echo createTask($USER->GetID(), $searcharray['pull'], $id_iblock, $PROP, $searcharray);
	}
}
// обновялем запись конец
/**
 * @author Oleynik Vlad 23.04.2019 <[<oleynikprog@gmail.com>]>
 * [createTask создание задачи]
 * @param  obj $modif_by 	[кто изменял]
 * @param  string $name  	[название элемента]
 * @param  array $property  [cвойства элемента]
 * @param  string $detail_text  	[подробное описание]
 * @param  string $section_id  	[id родительской секции]
 * @param  array $id_iblock  	[id инфоблока]
 * @param  string $id_task  	[id задачи]
 * @return [variable] [результат выполнения функции]
 */
function createTask($modif_by, $section_id, $id_iblock, $property, $element) {

	$status; //  статус выполонения функции
	$el = new CIBlockElement;
	$arLoadProductArray = Array(
		"ACTIVE" => "Y", // активен
		"MODIFIED_BY" => $modif_by, // элемент изменен текущим пользователем
		"IBLOCK_SECTION_ID" => $section_id, // элемент лежит в корне раздела
		"IBLOCK_ID" => $id_iblock,
		"PROPERTY_VALUES" => $property,
		"NAME" => $element['NAME'],
		"DETAIL_TEXT" => $element['DESCRIPTION'],
	);
	if ($_POST['id_elem'] == 'false') {
		if ($PRODUCT_ID = $el->Add($arLoadProductArray)) {
			$status = $PRODUCT_ID;

		} else {
			$status = 'Произошла ошибка при добавлении задачи' . $el->LAST_ERROR;
		}
	} else {
		if ($el->Update($_POST['id_elem'], $arLoadProductArray)) {
			$status = 'Задача обновлена';

		} else {
			$status = 'Произошла ошибка при обновлении задачи' . $el->LAST_ERROR;
		}
	}

	return $status;
}
/**
 * @author Oleynik Vlad 23.04.2019 <[<oleynikprog@gmail.com>]>
 * [createPull cоздание пула]
 * @param  obj $modif_by 	[кто изменял]
 * @param  string $name  	[название пулла]
 * @param  string $section_id  	[id родительской секции]
 * @param  string $code  	[символьный код пулла]
 * @param  array $sort  	[сортировка пулла]
 * @param  array $img  		[изображение пулла]
 * @param  array $deskr  	[описание пулла]
 * @param  array $id_iblock [id инфоблока]
 * @return variable $status [результат выполнения функции]
 */
function createPull($section_id, $id_iblock, $code, $name, $sort, $img, $deskr) {
	$status; //  статус выполонения функции
	$obj = new CIBlockSection;
	$arFields = Array(
		"ACTIVE" => "Y",
		"IBLOCK_SECTION_ID" => $section_id,
		"IBLOCK_ID" => $id_iblock,
		'CODE' => $code,
		"NAME" => $name,
		"SORT" => $sort,
		"PICTURE" => $img,
		"DESCRIPTION" => $deskr,
		"DESCRIPTION_TYPE" => 'none',
	);
	$id_sect = $obj->Add($arFields);
	if ($id_sect) {
		$status = $id_sect;
	} else {
		$status = "Произошла ошибка добавления пула";
	}
	return $status;
}
/**
 * @author Oleynik Vlad 23.04.2019 <[<oleynikprog@gmail.com>]>
 * [getProperies создание массива свойств]
 * @param  [obj] $data_form [данные с формы]
 * @return [array]          [массив свойств]
 */
function getProperies($data_form) {
	var_dump($data_form);
	$PROP['PLAN_HOURS'] = ($data_form['PLAN_HOURS'] != NULL) ? $data_form['PLAN_HOURS'] : 0;
	$PROP['PLAN_MINUTES'] = ($data_form['PLAN_MINUTES'] != NULL) ? $data_form['PLAN_MINUTES'] : 0;
	$PROP['MANAGER'] = $_POST['id_manag'];
	$PROP['USER'] = $_POST['id_resp'];
	$PROP['DEADLINE'] = $data_form['DEADLINE'];
	$PROP['ID_V_24'] = $data_form['ID_V_24'];
	$PROP['STATUS'] = $data_form['STATUS'];
	$PROP['PAID'] = $data_form['PAID'];
	// запись файла в свойство

	$PROP['FILES'] = $_FILES;

	return $PROP;
}