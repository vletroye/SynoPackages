<?php 
$A= $_POST["A"];
$B= $_POST["B"];
$C= $_POST["C"];
echo "Test a GET: <a href='index.php?page=test&A=$A&B=$B&C=$C'>A=$A, B=$B, C=$C</a><br /><br />";
echo "Test a Post: <br />";
$A= $_GET["A"];
$B= $_GET["B"];
$C= $_GET["C"];
echo "<form action='index.php?page=test' method='post'>
A: <input type='text' name='A' value='$A'><br>
B: <input type='text' name='B' value='$B'><br>
B: <input type='text' name='C' value='$C'><br>
<input type='submit'>
</form>
";
echo "<hr/>POST PARAMETERS:<br/>";
echo "<table>";
foreach ($_POST as $key => $value) {
	echo "<tr>";
	echo "<td>";
	echo $key;
	echo "</td>";
	echo "<td>: ";
	echo $value;
	echo "</td>";
	echo "</tr>";
}
echo "</table>";
echo "<hr/>GET PARAMETERS:<br/>";
echo "<table>";
foreach ($_GET as $key => $value) {
	echo "<tr>";
	echo "<td>";
	echo $key;
	echo "</td>";
	echo "<td>: ";
	echo $value;
	echo "</td>";
	echo "</tr>";
}
echo "</table>";
?>