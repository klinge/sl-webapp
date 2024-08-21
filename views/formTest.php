<?php 
$config = require './config/config.php';
$APP_DIR = $config['APP_DIR'];
// set page headers
$page_title = "STARTSIDAN";
include_once $APP_DIR . "/layouts/header.php";
?>

  <form action="formtest" method="POST">
    <input type="hidden" name="Content-Type" value="application/x-www-form-urlencoded">
    Name: <input type="text" name="name"><br>
    E-mail: <input type="text" name="email"><br>
    <label for="cars">Välj en bil:</label>
    <select name="cars" id="cars">
      <option value="volvo">Volvo</option>
      <option value="saab">Saab</option>
      <option value="mercedes">Peugeot</option>
      <option value="audi">Kia</option>
      <option value="mercedes">Mercedes</option>
    </select>
    <br/>
    <p>Best programming language: </p>
    <label>
      <input type="radio" name="editList" value="js" />JavaScript
    </label>
    <label>
      <input type="radio" name="editList" value="py" />Python
    </label>
    <label>
      <input type="radio" name="editList" value="cpp" />C++
    </label>
    <br />
    <input type="submit">
  </form>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

<?php // footer
    include_once $APP_DIR . "/layouts/footer.php";
?>