<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Document</title>
</head>
<body>
  <form action="userRgistration.php" method="post">
    <label for="">first name</label>
    <input type="text" name="first_name"><br><br>
    <label for="last_name">last name</label>
    <input type="text" name="last_name" id="last_name"><br><br>
    <label for="">phone Number</label>
    <input type="tel" name="phone_number"><br><br>
    <label for="">email</label>
    <input type="text" name="email" id=""><br><br>
    <label for="">password</label>
    <input type="password" name="password" id="password"><br><br>
    <label for="confirm_password">Confirm Password</label>
    <input type="password" name="confirm_password" id="confirm_password"><br><br>
    <label for="">Role</label>
    <input type="text" name="role" list="roles" Id="role">
    <datalist id="roles">
      
      <option value="Farmer" ></option>
      <option value="Buyer"></option>
      <option value="Admin"></option>
      <option value="input supplyer"></option>
      <option value="logistic operator"></option>
    </datalist><br><br>
    <button type="submit">submit</button>
  </form>
    
</body>
</html>