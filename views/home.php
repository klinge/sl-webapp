<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
</head>

<body>
  <h1>This is the homepage</h1>

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
</body>

</html>