<?php
require_once 'config/database.php';

$cnaes_comuns = [
    ['6201501', 'Desenvolvimento de programas de computador sob encomenda', 'J', 'baixo'],
    ['6202300', 'Desenvolvimento e licenciamento de programas de computador customizáveis', 'J', 'baixo'],
    ['6203100', 'Desenvolvimento e licenciamento de programas de computador não-customizáveis', 'J', 'baixo'],
    ['6204000', 'Consultoria em tecnologia da informação', 'J', 'baixo'],
    ['5611203', 'Lanchonetes, casas de chá, de sucos e similares', 'I', 'medio'],
    ['4711302', 'Mercearias', 'G', 'medio'],
    ['9602501', 'Cabeleireiros', 'S', 'baixo'],
    ['8599604', 'Cursos de idiomas', 'P', 'medio'],
    ['7020400', 'Atividades de consultoria em gestão empresarial', 'M', 'baixo'],
    ['6911701', 'Serviços advocatícios', 'M', 'medio'],
];

$db = Database::getInstance();
$conn = $db->getConnection();

foreach ($cnaes_comuns as $cnae) {
    try {
        $sql = "INSERT IGNORE INTO cnaes (codigo, descricao, secao, risco_fiscal) 
                VALUES (:codigo, :descricao, :secao, :risco)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':codigo' => $cnae[0],
            ':descricao' => $cnae[1],
            ':secao' => $cnae[2],
            ':risco' => $cnae[3]
        ]);
        echo "Inserido CNAE: {$cnae[0]} - {$cnae[1]}<br>";
    } catch (Exception $e) {
        echo "Erro ao inserir {$cnae[0]}: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Concluído! Agora o sistema deve funcionar corretamente.";
?>