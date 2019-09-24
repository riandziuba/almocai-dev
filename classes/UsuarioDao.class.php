<?php
	require_once("Conexao.class.php");
	require_once("Usuario.class.php");

	class UsuarioDao {


		/**
		 * INSERT
		 */

		public static function Insert(Usuario $usuario) {
			try {
				$sql = "INSERT INTO Usuario (matricula, senha, nome, tipo)
					VALUES(:matricula, :senha, :nome, :tipo)";

				$stmt = Conexao::conexao()->prepare($sql);

				$stmt->bindParam(":matricula", $codigo);
				$stmt->bindParam(":senha", $senha);
				$stmt->bindParam(":nome", $nome);
				$stmt->bindParam(":tipo", $tipo);

				$codigo = $usuario->getCodigo();
				$senha = $usuario->getSenha();
				$nome = $usuario->getNome();
				$tipo = $usuario->getTipo();

				return $stmt->execute();
			} catch (Exception $e){
				print "Erro ". $e->
				getCode() . " Mensagem: " . $e->getMessage();
			}
		}


		/**
		 * SELECT
		 */

		public static function Popula ($row) {
			$usuario = new Usuario;
			$usuario->setCodigo( $row['matricula'] );
			$usuario->setSenha( $row['senha'] );
			$usuario->setNome( $row['nome'] );
			$usuario->setTipo( $row['tipo'] );

			return $usuario;
		}

		public static function Select ($criterio, $pesquisa) {
			try {
				switch ($criterio) {
					case 'nome':
						$sql = "SELECT * FROM Usuario WHERE $criterio like '%$pesquisa%'";
						break;

					case 'matricula':
					case 'tipo':
						$sql = "SELECT * FROM Usuario WHERE $criterio = '$pesquisa'";
						break;

					case 'todos':
						$sql = "SELECT * FROM Usuario";
						break;
				}

				$query = Conexao::conexao()->query($sql);

				$usuarios = array();
				while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
					array_push($usuarios, self::Popula($row));
				}

				return $usuarios;
			} catch (Exception $e) {
				echo $e->getMessage();
			}
		}

		public static function Select2($tipo, $pesquisa)
		{
			// feita especificamente para a página de gerenciamento do administrador
			// seleciona por um tipo específico + uma pesquisa que pode ser tanto o nome qto a matrícula do aluno
			$sql = "SELECT * FROM Usuario WHERE tipo = '$tipo' ";
			if ($pesquisa != 'TODOS') {
				$sql .= " AND (nome like '%$pesquisa%' OR matricula like '%$pesquisa%')";
			}
				
			try {
				$bd = Conexao::conexao();
				$query = $bd->query($sql);
				$registros = array();
				while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
					array_push($registros, self::Popula($row));
				}
			} catch (PDOException $e) {
				echo "Erro (UsuarioDao::Select2): " . $e->getMessage();
			}

			return $registros;
		}

		public static function SelectPorMatricula ($matricula) {
			// Retorna o objeto aluno em vez de um array com um só objeto, que seria o resultado do Select ()
			$usuarios = self::Select('matricula', $matricula);
			return $usuarios[0];
		}

		public static function SelectPresenca($dia_cod, $user_mat) {
			$sql = "SELECT * FROM Presenca WHERE diaAlmoco_codigo = $dia_cod
			AND usuario_matricula = $user_mat";
			try {
				$query = Conexao::conexao()->query($sql);
				$row = $query->fetch(PDO::FETCH_ASSOC);
				return $row['presenca'];
			} catch (PDOException $e) {
				echo $e->getMessage();
			}
		}

		/**
		 * UPDATE
		 */

		public static function Update( Usuario $usuario) {
			$sql = "UPDATE Usuario SET nome = :nome, tipo = :tipo, senha = :senha,
			alimentacao = :alimentacao WHERE matricula = :matricula";
			try {
				$bd = Conexao::getInstance();
				$stmt = $bd->prepare($sql);
				
				$nome = $usuario->getNome();
				$stmt->bindParam(":nome", $nome);
				$tipo = $usuario->getTipo();
				$stmt->bindParam(":tipo", $tipo);
				$senha = $usuario->getSenha();
				$stmt->bindParam(":senha", $senha);
				$alimentacao = $usuario->getAlimentacao();
				$stmt->bindParam(":alimentacao", $alimentacao);
				$matricula = $usuario->getCodigo();
				$stmt->bindParam(":matricula", $matricula);
			} catch (PDOException $e) {
				echo "<b>Erro no preparo (UsuarioDao::Update): </b>".$e->getMessage();
			}

			return $stmt->execute();
		}

		public static function UpdateNome (Usuario $usuario) {
			// Só altera nome
			$sql = "UPDATE Usuario SET nome = :nome WHERE matricula = :matricula";
			try {
				$bd = Conexao::conexao();
				$stmt = $bd->prepare($sql);

				$nome = $usuario->getNome();
				$stmt->bindParam(":nome", $nome);
				$matricula = $usuario->getCodigo();
				$stmt->bindParam(":matricula", $matricula);

				return $stmt->execute();
			} catch (PDOException $e) {
				echo "Erro (UsuarioDao::UpdateNome): ".$e->getMessage();
			}
		}


		/**
		 * DELETE
		 */

		public static function Delete ($matricula) {
			$sql = "DELETE FROM Usuario WHERE matricula = :matricula";
			try {
				$bd = Conexao::conexao();
				$stmt = $bd->prepare($sql);
				$stmt->bindParam(":matricula", $matricula);
				return $stmt->execute();
			} catch (PDOException $e) {
				echo "Erro (UsuarioDao::Delete): ".$e->getMessage();
			}
		}


		/**
		 * LOGIN
		 */

		public static function Login(Usuario $usuario) {
			$matricula = $usuario->getCodigo();
			$senha = $usuario->getSenha();

			$sql = "SELECT * FROM Usuario
			WHERE `matricula` = '$matricula'
			AND `senha` = '$senha'";

			try { $query = Conexao::conexao()->query($sql);
			} catch (PDOException $e) { echo $e->getMessage(); }

			$row = $query->fetch(PDO::FETCH_ASSOC);

			$login_info = array();
			/** $login_info
			 * Informações que a função retornará:
			 * ['acao'] -> se o login deverá ser efetuado OU, caso contrário, qual foi o erro
			 * ['matricula'] -> matrícula do usuário
			 * ['nome'] -> nome do usuário
			 * ['tipo'] -> tipo do usuário (adm)
			 * ['matricula', 'nome' e 'tipo'] só serão preenchidas caso o login será feito
			 * serão armazenadas em $_SESSION
			 */

			if ($row) {
				$login_info['acao'] = "fazer_login";
				$login_info['matricula'] = $row['matricula'];
				$login_info['nome'] = $row['nome'];
				$login_info['tipo'] = $row['tipo']; //tipo (adm)
			} else {
				$login_info['acao'] = 'infos_incorretas';
			}
			return $login_info;
		}
	}
?>