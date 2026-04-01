<?php
class Database
{
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "weblaptop";
    private $conn;

    // Hàm khởi tạo – tự động kết nối 
    public function __construct()
    {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);

        if ($this->conn->connect_error) {
            die("Kết nối thất bại: " . $this->conn->connect_error);
        }

        // Thiết lập bộ mã UTF-8 
        $this->conn->set_charset("utf8");
    }

    // Truy vấn SELECT – trả về mảng dữ liệu 
    public function select($sql, $types = '', $params = [])
    {

        $stmt = $this->conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
    public function count($sql, $types = '', $params = [])
    {

        $stmt = $this->conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return $total;
    }
    // Thực thi lệnh INSERT, UPDATE, DELETE 
    // public function execute($sql, $type = '', $params = [])
    // {
    //     $stmt = $this->conn->prepare($sql);
    //     if (!empty($types)) {
    //         $stmt->bind_param($types, ...$params);
    //     }
    //     $row = $stmt->execute();

    //     $stmt->close();
    //     return $row;
    // }
    public function execute($sql, $types = '', $params = []) 
    {
    $stmt = $this->conn->prepare($sql);
    
    // Kiểm tra: Nếu có tham số truyền vào thì mới bind
    if (!empty($types) && !empty($params)) {
        // Chú ý dùng đúng biến $types đã khai báo ở trên
        $stmt->bind_param($types, ...$params); 
    }
    
    $row = $stmt->execute();
    $stmt->close();
    return $row;
    }

    // THÊM HÀM NÀY: Để lấy ID mới nhất vừa insert
    public function lastInsertId() 
    {
        return $this->conn->insert_id;
    }

    // Hàm INSERT – Trả về ID của bản ghi vừa thêm
public function insert($sql, $types = '', $params = [])
{
    $stmt = $this->conn->prepare($sql);
    if (!empty($types)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $last_id = $this->conn->insert_id; // Lấy ID vừa tự động tạo (order_id)
        $stmt->close();
        return $last_id; 
    } else {
        $stmt->close();
        return false;
    }
}

    // Hàm ngắt kết nối 
    public function close()
    {
        $this->conn->close();
    }
}
