<?php
	require_once 'classes/markupeditor.php';
	require_once 'classes/editcommand.php';

	$args = ($_SERVER['REQUEST_METHOD'] == "POST") ? $_POST : $_GET;

	$refreshMap = false;
	$errorMsg = null;
	$errorDetail = null;

	try
	{
		MgInitializeWebTier($CONFIG_FILE);
		
		$markupEditor = new MarkupEditor($args);
		
		if (array_key_exists('EDITCOMMAND', $args))
		{
			$cmd = $args['EDITCOMMAND'];
			switch ($cmd) {
			case EditCommand::AddPoint:
				$markupEditor->AddPoint();
				$refreshMap = true;
				break;
			case EditCommand::AddLine:
			case EditCommand::AddLineString:
				$markupEditor->AddLineString();
				$refreshMap = true;
				break;
			case EditCommand::AddRectangle:
			case EditCommand::AddPolygon:
				$markupEditor->AddPolygon();
				$refreshMap = true;
				break;
			case EditCommand::Delete:
				$markupEditor->DeleteMarkup();
				$refreshMap = true;
				break;
			case EditCommand::Update:
				$markupEditor->UpdateMarkup();
				$refreshMap = true;
				break;
				
			}
		}
		
		$markupFeatures = $markupEditor->GetMarkupFeatures();
	}
	catch (MgException $e)
	{
		$errorMsg = $e->GetMessage();
		$errorDetail = $e->GetDetails();
	}
?>
<html>
<head>
	<title>Edit Markup</title>
    <link rel="stylesheet" href="styles/gt.css" type="text/css">
    <script language="javascript" src="../../layers/MapGuide/MapGuideViewerApi.js"></script>
    <script language="javascript" src="../../common/browserdetect.js"></script>
	<script language="javascript">
        var session = '<?= $args['SESSION'] ?>';
        var mapName = '<?= $args['MAPNAME'] ?>';
    
		var CMD_ADD_POINT		= <?= EditCommand::AddPoint ?>;
		var CMD_ADD_LINE 		= <?= EditCommand::AddLine ?>;
		var CMD_ADD_LINESTRING 	= <?= EditCommand::AddLineString ?>;
		var CMD_ADD_RECTANGLE 	= <?= EditCommand::AddRectangle ?>;
		var CMD_ADD_POLYGON 	= <?= EditCommand::AddPolygon ?>;
		var CMD_DELETE 			= <?= EditCommand::Delete ?>;
		var CMD_UPDATE 			= <?= EditCommand::Update ?>;
	
		var EDIT_DEFAULT_HELP = "To begin select the type of markup to digitize.";
		var EDIT_POINT_HELP = "Click the map to specify the location of the point.";
		var EDIT_LINE_HELP = "Click and drag on the map to specify the start and end points of the line.";
		var EDIT_LINESTRING_HELP = "Click the map to define each point of the line, Double Click on the final point to end.";
		var EDIT_RECTANGLE_HELP = "Click on the map to specify the first corner and drag the mouse to define the rectangle.";
		var EDIT_POLYGON_HELP = "Click the map to define each point of the polygon, Double Click on the final point to end.";
	
		function SetDigitizeInfo(text)
		{
			var digitizeInfo = document.getElementById("digitizeInfo");
			digitizeInfo.innerHTML = text;
		}

		function SubmitCommand(cmd)
		{
			var commandInput = document.getElementById("commandInput");
			commandInput.value = cmd;
			
			var editForm = document.getElementById("editForm");
			editForm.submit();
		}
	
		function AddPoint()
		{
			SetDigitizeInfo(EDIT_POINT_HELP);
			DigitizePoint(OnPointDigitized);
		}

		function AddLine()
		{
			SetDigitizeInfo(EDIT_LINE_HELP);
			DigitizeLine(OnLineStringDigitized);
		}

		function AddLineString()
		{
			SetDigitizeInfo(EDIT_LINESTRING_HELP);
			DigitizeLineString(OnLineStringDigitized);
		}

		function AddRectangle()
		{
			SetDigitizeInfo(EDIT_RECTANGLE_HELP);
			DigitizeRectangle(OnRectangleDigitized);
		}

		function AddPolygon()
		{
			SetDigitizeInfo(EDIT_POLYGON_HELP);
			DigitizePolygon(OnPolyonDigitized);
		}

		function PromptAndSetMarkupText()
		{
			var textInput = document.getElementById("textInput");

			textLabel = window.prompt("Enter a label for the markup item", "");
			textInput.value = (textLabel != null) ? textLabel : "";
            
            ClearDigitization();
		}
	
		function OnPointDigitized(point)
		{
			PromptAndSetMarkupText();			

			var geometryInput = document.getElementById("geometryInput");
			geometryInput.value = point.X + "," + point.Y;
			
			SubmitCommand(CMD_ADD_POINT);
		}

		function OnLineStringDigitized(lineString)
		{
			PromptAndSetMarkupText();			

			var geomText = lineString.Count;
			for (var i = 0; i < lineString.Count; i++)
			{
				geomText += "," + lineString.Point(i).X + "," + lineString.Point(i).Y;
			}
		
			var geometryInput = document.getElementById("geometryInput");
			geometryInput.value = geomText;

			SubmitCommand(CMD_ADD_LINESTRING);
		}

		function OnRectangleDigitized(rectangle)
		{
			PromptAndSetMarkupText();			

			var geometryInput = document.getElementById("geometryInput");
			geometryInput.value = "5," 
				+ rectangle.Point1.X + "," + rectangle.Point1.Y + ","
				+ rectangle.Point2.X + "," + rectangle.Point1.Y + ","
				+ rectangle.Point2.X + "," + rectangle.Point2.Y + ","
				+ rectangle.Point1.X + "," + rectangle.Point2.Y + ","
				+ rectangle.Point1.X + "," + rectangle.Point1.Y;

			SubmitCommand(CMD_ADD_RECTANGLE);
		}
	
		function OnPolyonDigitized(polygon)
		{
			PromptAndSetMarkupText();			

			var geomText = polygon.Count;
			for (var i = 0; i < polygon.Count; i++)
			{
				geomText += "," + polygon.Point(i).X + "," + polygon.Point(i).Y;
			}
		
			var geometryInput = document.getElementById("geometryInput");
			geometryInput.value = geomText;

			SubmitCommand(CMD_ADD_POLYGON);
		}
	
		function SelectMarkup()
		{            
			markupFeatures = document.getElementById("markupFeatures");
			
            reqParams = "MAPNAME=" + encodeURIComponent(mapName);
			reqParams += "&SESSION=" + encodeURIComponent(session);
			reqParams += "&OPENMARKUP=" + encodeURIComponent('<?= $args['OPENMARKUP']; ?>');
			reqParams += "&MARKUPFEATURE=" + markupFeatures.value;
            
            if(msie)
                reqHandler = new ActiveXObject("Microsoft.XMLHTTP");
            else
                reqHandler = new XMLHttpRequest();
                
            reqHandler.open("POST", "getselectionxml.php", false);
            reqHandler.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

			reqHandler.send(reqParams);
			if(reqHandler.responseXML)
			{
				SetSelectionXML(reqHandler.responseText);
			}
		}
	
		function DeleteMarkup()
		{
			SubmitCommand(CMD_DELETE);
		}
		
		function UpdateMarkup()
		{
			SubmitCommand(CMD_UPDATE);
		}
	
		function CloseEditor()
		{
			var editForm = document.getElementById("editForm");
			editForm.action = "markupmain.php";
			
			editForm.submit();
		}
	
		function OnMarkupFeatureChange()
		{
            var markupFeatures = document.getElementById("markupFeatures");
			var updateTextInput = document.getElementById("updateTextInput");
            var selectBtn = document.getElementById("selectBtn");
            var deleteBtn = document.getElementById("deleteBtn");
			var updateBtn = document.getElementById("updateBtn");
			
			if (markupFeatures.selectedIndex >= 0)
			{
				value = markupFeatures.options[markupFeatures.selectedIndex].text;
				if (value != '[no text]')
					updateTextInput.value = value;
				else
					updateTextInput.value = '';
					
				selectBtn.disabled = false;
				deleteBtn.disabled = false;
				updateBtn.disabled = false;
			}
			else
			{
				updateTextInput.value = '';
				selectBtn.disabled = true;
				deleteBtn.disabled = true;
				updateBtn.disabled = true;
			}
		} 
	
		function OnLoad()
		{
			OnMarkupFeatureChange();
						
		<?php if ($refreshMap) { ?>
			var map = parent.Fusion.getMapByName(mapName);
            map.reloadNamedMap(mapName);
		<?php } ?>
			SetDigitizeInfo(EDIT_DEFAULT_HELP);
		}
	</script>
</head>

<body onLoad="OnLoad()" marginwidth=5 marginheight=5 leftmargin=5 topmargin=5 bottommargin=5 rightmargin=5>

<?php if ($errorMsg == null) { ?>

<form action="editmarkup.php" method="post" enctype="application/x-www-form-urlencoded" id="editForm" target="_self">
<table class="RegText" border="0" cellspacing="0" width="100%">
	<tr>
		<td colspan="2" class="Title">Edit Markup - <?= $markupEditor->GetMarkupName() ?><hr></td>
	</tr>

	<tr>
		<td colspan="2" class="SubTitle">Add Markup</td>
	</tr>
	<tr>
		<td colspan="2">Digitize markup:</td>
	</tr>
	<tr><td colspan="2" height="2px"></td></tr>
	<tr>
		<td colspan="2">
			<input class="Ctrl" id="pointBtn" type="button" onClick="AddPoint()" value="Point" style="width:60px">
			<input class="Ctrl" id="lineBtn" type="button" onClick="AddLine()" value="Line" style="width:60px">
			<input class="Ctrl" id="lineStringBtn" type="button" onClick="AddLineString()" value="Line String" style="width:60px">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<input class="Ctrl" id="rectangleBtn" type="button" onClick="AddRectangle()" value="Rectangle" style="width:60px">
			<input class="Ctrl" id="polygonBtn" type="button" onClick="AddPolygon()" value="Polygon" style="width:60px">
		</td>
	</tr>
	<tr><td colspan="2" height="2px"></td></tr>
	<tr>
		<td class="InfoText" colspan="2" id="digitizeInfo"></td>
	</tr>
	<tr><td colspan="2"></td></tr>
	<tr>
		<td colspan="2" class="SubTitle">Modify Markup</td>
	</tr>
	<tr>
		<td colspan="2">
			<input class="Ctrl" name="UPDATETEXT" type="text" id="updateTextInput" maxlength="255" style="width:100%"><br></td>
		</td>
	</tr>
	<tr>
		<td class="RegText">
			<select name="MARKUPFEATURE" size="15" class="Ctrl" id="markupFeatures" onChange="OnMarkupFeatureChange()" style="width: 100%">
				<?php
					$selected = 'selected';
					foreach($markupFeatures as $markupId => $markupText) {
				?>
				<option value="<?= $markupId ?>" <?=$selected ?> ><?= (strlen($markupText) > 0) ? $markupText : '[no text]' ?></option> 
				<?php
						$selected = ''; 
					} 
				?>
		  	</select>
		</td>
	</tr>
	<tr><td colspan="2" height="2px"></td></tr>
	<tr>
		<td colspan="2">
			<input class="Ctrl" id="selectBtn" type="button" onClick="SelectMarkup()" value="Select Markup" style="width:80px">
			<input class="Ctrl" id="deleteBtn" type="button" onClick="DeleteMarkup()" value="Delete Markup" style="width:80px">
			<input class="Ctrl" id="updateBtn" type="button" onClick="UpdateMarkup()" value="Update Text" style="width:80px">
		</td>
	</tr>
	<tr><td colspan="2" height="2px"></td></tr>

	<tr>
		<td colspan="2" align="right">
			<hr>
			<input class="Ctrl" name="" type="button" onClick="CloseEditor()" value="Close" style="width:60px">
		</td>
	</tr>

</table>
<input name="SESSION" type="hidden" value="<?= $args['SESSION'] ?>">
<input name="MAPNAME" type="hidden" value="<?= $args['MAPNAME'] ?>">
<input name="OPENMARKUP" type="hidden" value="<?= $args['OPENMARKUP'] ?>">
<input name="EDITCOMMAND" type="hidden" value="" id="commandInput">
<input name="GEOMETRY" type="hidden" value="" id="geometryInput">
<input name="TEXT" type="hidden" value="" id="textInput">
</form>

<?php } else { ?>

<table class="RegText" border="0" cellspacing="0" width="100%%">
	<tr><td class="Title">Error<hr></td></tr>
	<tr><td><?= $errorMsg ?></td></tr>
	<tr><td><?= $errorDetail ?></td></tr>
</table>

<?php } ?>

</body>

</html>
