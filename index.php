<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
<?php
try {
$name = new field_text(
    "name",
    "Имя",
    true,
    $_POST['name']
);

$password = new field_password(
    "pass",
    "Пароль",
    true,
    $_POST['pass']
);

$fst = new field_select(
    "fst",
    "Выбор множества<br> значения",
    array("Первый",
        "Второй",
        "Третий"),
        array(0, 2),
        true,
        3
);

$snd = new field_select(
    "snd",
    "Выбор одного<br> значения",
    array("Первый",
        "Второй",
        "Третий"),
        0
);

$form = new form(array(
    "fst" => $fst,
    "snd" => $snd),
    $button_name
);

if(!empty($_POST)){
    // require_once("config.php");
    $error = $form->check();
    if (empty($error)){
        $query = "INSERT INTO users
                    VALUES(NULL,
                            '{$form->fields[name]->value}',
                            MD5('{$form->fields[pass]->value}'),
                            NOW())";
        if (!mysql_query($query)){
            throw new ExceptionMySql(mysql_error(),
                $query,
                "Ошибка регистрации пользователя");
        }

        header("Location: $_SERVER[PHP_SELF]");

        exit();
    }
}

if(!empty($error)){
    foreach($error as $err){
        echo "<span style=\"color:red\">$err</span><br>";
    }
}
$form->print_form();
}
catch(ExceptionObject $exc){
    echo "<p class=help>Произошла ошибка (ExceptionObject). {$exc->getMessage()}.</p>";
    echo "<p class=help>Ошибка в файле {$exc->getFile()} в строке {$exc->getLine()}.</p>";

    exit();
}
catch(ExceptionMember $exc){
    echo "<p class=help>Произошла исключительная ситуация (ExceptionObject). {$exc->getMessage()}.</p>";
    echo "<p class=help>Ошибка в файле {$exc->getFile()} в строке {$exc->getLine()}.</p>";

    exit();
}
catch(ExceptionMySQL $exc){
    echo "<p class=help>Произошла исключительная ситуация (ExceptionMySQL).</p>";
    echo "<p class=help>{$exc->getMySQLError()}<br>
            ".n12br($exc->getSQLQuery())."</p>";
    echo "<p class=help>Ошибка в файле {$exc->getFile()} в строке {$exc->getLine()}.</p>";

    exit();
}


abstract class field
{
    protected $name;
    protected $type;
    protected $caption;
    protected $value;
    protected $is_required;

    function _construct(
        $name,
        $type,
        $caption,
        $is_required = false,
        $value = "")

        {
            $this->name = $this->encodestring($name);
            $this->type = $type;
            $this->caption = $caption;
            $this->is_required = $is_required;
            $this->value = $value;
        }

        abstract function check();

        abstract function get_html();

        public function _get($key) {
            if(isset($this->$key))
            return $this->$key;
            else {
                throw new ExceptionMember($key,
                "Член "._CLASS_."::$key не существует");
            }
        }

        protected function encodestring($st){
            $st=strtr($st, "абвгдеезийклмнопрстуфхъыэ_", "abvgdeeziyklmnoprstufh'iei");
            $st=strtr($st, "АБВГДЕЕЗИЙКЛМНОПРСТУФХЪЫЭ_", "ABVGDEEZIYKLMNOPRSTUFH'IEI");
            $st=strtr($st, 
            array(            
                "Ж" => "ZH", "Ц" => "TS", "Ч" => "CH",
                "Ш" => "SH", "Щ" => "SHCH", "Ь" => "",
                "Ю" => "YU", "Я" => "YA",
                "ж" => "zh", "ц" => "ts", "ч" => "ch",
                "ш" => "sh", "щ" => "shch", "ь" => "",
                "ю" => "yu", "я" => "ya"            
            )
        );
        return $st;
    }

}

class field_text extends field 
{
    public $size; 

    function _construct(
        $name,
        $caption,
        $is_required = false,
        $value = "",
        $size = 41)

        {
        parent::_construct(
            $name,
            "text",
            $caption,
            $is_required,
            $value);

            $this->$size = $size;
        }

        function get_html()
        {
         if(!empty($this->size))  {
             $size = "size=".$this->size;
         }
         else $size = "";

         $tag = "<input $style $class
                    type=\"".$this->type."\"
                    name=\"".$this->name."\"
                    value=\"".
                    htmlspecialchars($this->value, ENT_QUOTES)."\"
                    $size >\n";

         if($this->is_required) {
             $this->caption .= " *";
         }
        
         return array($this->caption, $tag);
        }

        function check()
        {
            if(!get_magic_quotes_gpc())
            {
                $this->value = mysql_escape_string($this->value);
            }

            if ($this->is_required)
            {
                if(empty($this->value))
                {
                    return "Поле \"".$this->caption."\" не заполнено";
                }
            }
            return "";
        }

}

class form {
    public $fields;
    protected $button_name;
    
    public function _construct(
        $flds,
        $button_name)
        
        {
            $this->fields = $flds;
            $this->button_name = $button_name;
        
        foreach($flds as $key => $obj)
        {
            if(!is_subclass_of($obj, "field"))
            {
                throw new ExceptionObject($key,
                    "\"$key\" не является элементом управления");
            }
        }
    }
 

public function print_form(){
    $enctype = "";
    if(!empty($this->$fields))
    {
        foreach($this->fields as $obj)
        {
            if($obj->type == "file"){
                $enctype = "enctype='multipart/form-data'";
            }
        }
    }

    echo "<form name=form $enctype method=post>";
    echo "<table>";
    if(!empty($this->fields)){
        foreach($this->fields as $obj)
        {
            list($caption, $tag, $alternative) = $obj->get_html();
            if(is_array($tag)) $tag = implode("<br>",$tag);
            echo "<tr>
                    <td width=100 </td> 
                </tr>\n";
        }
    }

    echo "<tr>
            <input class=button
                type=submit
                value=\"".htmlspecialchars($this->button_name, ENT_QUOTES)."\">
            </tr>\n";
            echo "</table>";
            echo "</form>";               
}

public function _toString(){
    $this->print_form();
}

public function check(){
    $arr = array();
    if(!empty($this->fields)){
        foreach($this->fields as $obj){
            $str =$obj->check();
            if(!empty($str)) $arr[] = $str;
        }
    }
    return $arr;
}

public function sendToData()    //Заполнение базы данных 
{
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "SignupLabDB";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    
    } 
        $sql = "INSERT INTO `users` (`fields`)
        VALUES ($this->fields')";


    if ($conn->query($sql) === TRUE) {
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
$conn->close();
}

}

class field_password extends field_text
{
    function _construct(
        $name,
        $is_required,
        $value = "",
        $size = 41)
        
        {
            parent::_construct(
                $name,
                $is_required,
                $value,
                $size);

                $this->type = "password";
        }
}

class field_email extends field_text
{
    function check()
    {
        if($this->is_required || !empty($this->value)){
            $pattern = "#^[-0-9a-z_\.]+@[-0-9a-z^\.]+\.[a-z]{2,6}$#i";
            if (!preg_match($pattern, $this->value)){
                return "Введите email в виде <i>youremail@server.com</i>";
            }
        }
        return "";
    }
}

class field_checkbox extends field
{
    function _construct(
        $name,
        $caption,
        $value = false)

        {
            parent::_construct(
                $name,
                "checkbox",
                $caption,
                false,
                $value 
            );

            if($value == "on") $this->value = true;
            else if($value === true) $this->value = true;
            else $this->value = false;
        }
    function get_html(){
        if($this->value) $checked = "checked";
        else $checked = "";
        $tag = "<input 
                type=\"".$this->type."\"
                name=\"".$this->name."\"
                $checked>\n";
        return array($this->caption, $tag);
    }

    function check(){
        return "";
    }
}

class field_select extends field
{
    protected $options;
    protected $multi;
    protected $select_size;

    function _construct(
        $name,
        $caption,
        $options = array(),
        $value,
        $multi = false,
        $select_size = 4
    )

    {
        parent::_construct(
            $name,
            "select",
            $caption,
            false,
            $value
        );

        $this->options = $options;
        $this->multi = $multi;
        $this->select_size = $select_size;
    }
    function get_html(){
        if($this->multi && $this->select_size){
            $multi = "multiple seze=".$this->select_size;
            $this->name = $this->name."[]";
        }
        else $multi = "";
        $tag = "<select name=\"".$this->name."\" $multi>\n";
        if (!empty($this->options)){
            foreach($this->options as $key => $value){
                if(is_array($this->value)){
                    if(in_array($key,$this->value)) $selected = "selected";
                    else $selected = "";
                }
                else if($key == trim($this->value)) $selected = "selected";
                else $selected = "selected";
                    $tag .= "<options value='".
                            htmlspecialchars($key, ENT_QUOTES)
                            ."' $selected>".
                            htmlspecialchars($value, ENT_QUOTES)
                            ."</options>\n";
                }
            }
            $tag .= "</selected>\n";
            return array($this->caption, $tag);
        }
        function check()
        {
            if(in_array($this->value,array_keys($this->options))){
                if(empty($this->value)){
                    return "Поле \"".$this->caption."\" содержит недопустимое значение";
                }
            }
            // if (!get_magic_quotes_gpc()){
            //     for($i = 0;i < count($this->value); $i++){
            //         $this->value[$i] = mysql escape string($this->value[$i]);
            //     }
            // }
            return "";
        }
        function selected(){
            return $this->value[0];
        }
}

        

?>
</body>
</html>

