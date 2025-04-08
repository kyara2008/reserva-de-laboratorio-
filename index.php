<!-- index.php -->
<?php
session_start();

// Inicializa as reservas e professores por laboratório se ainda não existirem
if (!isset($_SESSION['reservas'])) {
    $_SESSION['reservas'] = [];
}
if (!isset($_SESSION['professores_lab'])) {
    $_SESSION['professores_lab'] = [];
}

// Dados básicos
$laboratorios = ['Laboratório 1', 'Laboratório 2', 'Laboratório 3'];
$turnos = ['Manhã', 'Tarde', 'Noite'];
$aulas_por_turno = 5;

// Processa a reserva ou remoção via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['chave']) && isset($_POST['professor'])) {
        $chave = $_POST['chave'];
        $professor = trim($_POST['professor']);
        $lab = explode('-', $chave)[0];

        // Verifica se o laboratório já tem um professor diferente
        if (empty($professor)) {
            exit('Professor não informado');
        }
        if (isset($_SESSION['professores_lab'][$lab]) && $_SESSION['professores_lab'][$lab] !== $professor) {
            exit('Este laboratório já está reservado por outro professor');
        }

        // Reserva a aula
        if (!isset($_SESSION['reservas'][$chave])) {
            $_SESSION['reservas'][$chave] = $professor;
            $_SESSION['professores_lab'][$lab] = $professor;
        }
        exit;
    }

    if (isset($_POST['desmarcar'])) {
        $chave = $_POST['desmarcar'];
        $lab = explode('-', $chave)[0];
        unset($_SESSION['reservas'][$chave]);
        
        // Remove o professor do laboratório se não houver mais reservas
        $tem_reserva = false;
        foreach ($_SESSION['reservas'] as $key => $value) {
            if (strpos($key, $lab) === 0) {
                $tem_reserva = true;
                break;
            }
        }
        if (!$tem_reserva) {
            unset($_SESSION['professores_lab'][$lab]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserva de Laboratórios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        
        .schedule-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        .turno-header {
            background-color: #4CAF50;
            color: white;
            width: 100px;
        }
        
        .lab-header {
            background-color: #2196F3;
            color: white;
            font-weight: bold;
        }
        
        .aula {
            cursor: pointer;
            padding: 5px;
            margin: 2px;
            background-color: #fff;
            border-radius: 3px;
        }
        
        .aula.reserved {
            background-color: #ffcccc;
        }
        
        .prof-input {
            margin-bottom: 20px;
        }
        
        input[type="text"] {
            padding: 8px;
            width: 200px;
        }
    </style>
</head>
<body>
    <div class="schedule-container">
        <!-- Campo para inserir o nome do professor -->
        <div class="prof-input">
            <label>Nome do Professor: </label>
            <input type="text" id="professor" placeholder="Digite seu nome">
        </div>

        <!-- Tabela interativa -->
        <table>
            <tr>
                <th>Turno</th>
                <?php foreach ($laboratorios as $lab) { ?>
                    <th class="lab-header"><?php echo $lab; ?></th>
                <?php } ?>
            </tr>
            <?php foreach ($turnos as $turno) { ?>
                <tr>
                    <th class="turno-header"><?php echo $turno; ?></th>
                    <?php foreach ($laboratorios as $lab) { ?>
                        <td>
                            <?php
                            for ($i = 1; $i <= $aulas_por_turno; $i++) {
                                $chave = "$lab-$turno-$i";
                                $reservado = isset($_SESSION['reservas'][$chave]);
                                $classe = $reservado ? 'aula reserved' : 'aula';
                                $texto = $reservado ? $_SESSION['reservas'][$chave] . " - Aula $i" : "Aula $i";
                                echo "<div class='$classe' data-chave='$chave'>$texto</div>";
                            }
                            ?>
                        </td>
                    <?php } ?>
                </tr>
            <?php } ?>
        </table>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const aulas = document.querySelectorAll('.aula');
            const professorInput = document.getElementById('professor');

            aulas.forEach(aula => {
                aula.addEventListener('click', () => {
                    const chave = aula.getAttribute('data-chave');
                    const professor = professorInput.value.trim();
                    const isReserved = aula.classList.contains('reserved');

                    if (isReserved) {
                        // Desmarcar reserva
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `desmarcar=${chave}`
                        })
                        .then(response => {
                            if (response.ok) {
                                aula.classList.remove('reserved');
                                aula.textContent = aula.textContent.split(' - ')[1]; // Remove o nome do professor
                            }
                        })
                        .catch(error => console.error('Erro:', error));
                    } else if (professor) {
                        // Reservar aula
                        fetch(window.location.href, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `chave=${chave}&professor=${encodeURIComponent(professor)}`
                        })
                        .then(response => {
                            if (response.ok) {
                                aula.classList.add('reserved');
                                aula.textContent = `${professor} - ${aula.textContent}`;
                            } else {
                                response.text().then(text => alert(text));
                            }
                        })
                        .catch(error => console.error('Erro:', error));
                    } else {
                        alert('Por favor, insira o nome do professor!');
                    }
                });
            });
        });
    </script>
</body>
</html>