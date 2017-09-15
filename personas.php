<?php

require '/../datos/ConexionBD.php';

class personas
{
    // Datos de la tabla "personas"
    const NOMBRE_TABLA = "personas";
    const ID = "id";
    const NOMBRE = "nombre";
	const APELLIDO = "apellido";
	const DNI = "dni";
    const EMAIL = "email";
	const USUARIO = "usuario";
    const CONTRASENA = "contrasena";
    const CLAVE_API = "claveApi";

    const ESTADO_CREACION_EXITOSA = 1;
    const ESTADO_CREACION_FALLIDA = 2;
    const ESTADO_ERROR_BD = 3;
    const ESTADO_AUSENCIA_CLAVE_API = 4;
    const ESTADO_CLAVE_NO_AUTORIZADA = 5;
    const ESTADO_URL_INCORRECTA = 6;
    const ESTADO_FALLA_DESCONOCIDA = 7;
    const ESTADO_PARAMETROS_INCORRECTOS = 8;

    public static function post($peticion)
    {
        if ($peticion[0] == 'registro') {
            return self::registrar();
        } else if ($peticion[0] == 'login') {
            return self::loguear();
        } else {
            throw new ExcepcionApi(self::ESTADO_URL_INCORRECTA, "Url mal formada", 400);
        }
    }


    /**
     * Crea una nueva persona en la base de datos
     */
    private function registrar()
    {
        $cuerpo = file_get_contents('php://input');
        $personas = json_decode($cuerpo);

        $resultado = self::crear($personas);

        switch ($resultado) {
            case self::ESTADO_CREACION_EXITOSA:
                http_response_code(200);
                return
                    [
                        "estado" => self::ESTADO_CREACION_EXITOSA,
                        "mensaje" => utf8_encode("¡Registro con éxito!")
                    ];
                break;
            case self::ESTADO_CREACION_FALLIDA:
                throw new ExcepcionApi(self::ESTADO_CREACION_FALLIDA, "Ha ocurrido un error");
                break;
            default:
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA, "Falla desconocida", 400);
        }
    }

    /**
     * Crea una nueva persona en la tabla "personas"
     * @param mixed $datosUsuario columnas del registro
     * @return int codigo para determinar si la inserción fue exitosa
     */
    private function crear($datosPersona)
    {
        $nombre = $datosPersona->nombre;
		$apellido = $datosPersona->apellido;
        $dni = $datosPersona->dni;
        $email = $datosPersona->email;
        $usuario = $datosPersona->usuario;
        $contrasena = $datosPersona->contrasena;
        $contrasenaEncriptada = self::encriptarContrasena($contrasena);
        $claveApi = self::generarClaveApi();

        try {

            $pdo = ConexionBD::obtenerInstancia()->obtenerBD();

            // Sentencia INSERT
            $comando = "INSERT INTO " . self::NOMBRE_TABLA . " ( " .
                self::NOMBRE . "," .
				self::APELLIDO . "," .
				self::DNI . "," .
				self::EMAIL . "," .
				self::USUARIO . "," .
                self::CONTRASENA . "," .
                self::CLAVE_API . "," .
                " VALUES(?,?,?,?,?,?,?)";

            $sentencia = $pdo->prepare($comando);

            $sentencia->bindParam(1, $nombre);
			$sentencia->bindParam(2, $apellido);
			$sentencia->bindParam(3, $dni);
			$sentencia->bindParam(4, $email);
			$sentencia->bindParam(5, $usuario);
            $sentencia->bindParam(6, $contrasenaEncriptada);
            $sentencia->bindParam(7, $claveApi);

            $resultado = $sentencia->execute();

            if ($resultado) {
                return self::ESTADO_CREACION_EXITOSA;
            } else {
                return self::ESTADO_CREACION_FALLIDA;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }

    }

    /**
     * Protege la contraseña con un algoritmo de encriptado
     * @param $contrasenaPlana
     * @return bool|null|string
     */
    private function encriptarContrasena($contrasenaPlana)
    {
        if ($contrasenaPlana)
            return password_hash($contrasenaPlana, PASSWORD_DEFAULT);
        else return null;
    }

    private function generarClaveApi()
    {
        return md5(microtime() . rand());
    }

    private function loguear()
    {
        $respuesta = array();

        $body = file_get_contents('php://input');
        $persona = json_decode($body);

        $usuario = $persona->usuario;
        $contrasena = $persona->contrasena;


        if (self::autenticar($usuario, $contrasena)) {
            $personaDB = self::obtenerPersonaPorUsuario($usuario);

            if ($personaDB != NULL) {
                http_response_code(200);
                $respuesta["nombre"] = $personaDB["nombre"];
				$respuesta["apellido"] = $personaDB["apellido"];
				$respuesta["dni"] = $personaDB["dni"];
				$respuesta["email"] = $personaDB["email"];
				$respuesta["usuario"] = $personaDB["usuario"];
				$respuesta["contrasena"] = $personaDB["contrasena"];
                $respuesta["claveApi"] = $personaDB["claveApi"];
                return ["estado" => 1, "persona" => $respuesta];
            } else {
                throw new ExcepcionApi(self::ESTADO_FALLA_DESCONOCIDA,
                    "Ha ocurrido un error");
            }
        } else {
            throw new ExcepcionApi(self::ESTADO_PARAMETROS_INCORRECTOS,
                utf8_encode("Usuario o contraseña inválidos"));
        }
    }

    private function autenticar($usuario, $contrasena)
    {
        $comando = "SELECT contrasena FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::USUARIO . "=?";

        try {

            $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

            $sentencia->bindParam(1, $usuario);

            $sentencia->execute();

            if ($sentencia) {
                $resultado = $sentencia->fetch();

                if (self::validarContrasena($contrasena, $resultado['contrasena'])) {
                    return true;
                } else return false;
            } else {
                return false;
            }
        } catch (PDOException $e) {
            throw new ExcepcionApi(self::ESTADO_ERROR_BD, $e->getMessage());
        }
    }

    private function validarContrasena($contrasenaPlana, $contrasenaHash)
    {
        return password_verify($contrasenaPlana, $contrasenaHash);
    }


    private function obtenerPersonaPorUsuario($usuario)
    {
        $comando = "SELECT " .
            self::NOMBRE . "," .
			self::APELLIDO . "," .
			self::DNI . "," .
			self::EMAIL . "," .
			self::USUARIO . "," .
            self::CONTRASENA . "," .
            self::CLAVE_API . "," .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::USUARIO . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $usuario);

        if ($sentencia->execute())
            return $sentencia->fetch(PDO::FETCH_ASSOC);
        else
            return null;
    }

    /**
     * Otorga los permisos a un usuario para que acceda a los recursos
     * @return null o el id del usuario autorizado
     * @throws Exception
     */
    public static function autorizar()
    {
        $cabeceras = apache_request_headers();

        if (isset($cabeceras["Authorization"])) {

            $claveApi = $cabeceras["Authorization"];

            if (personas::validarClaveApi($claveApi)) {
                return personas::obtenerIdPersona($claveApi);
            } else {
                throw new ExcepcionApi(
                    self::ESTADO_CLAVE_NO_AUTORIZADA, "Clave de API no autorizada", 401);
            }

        } else {
            throw new ExcepcionApi(
                self::ESTADO_AUSENCIA_CLAVE_API,
                utf8_encode("Se requiere Clave del API para autenticación"));
        }
    }

    /**
     * Comprueba la existencia de la clave para la api
     * @param $claveApi
     * @return bool true si existe o false en caso contrario
     */
    private function validarClaveApi($claveApi)
    {
        $comando = "SELECT COUNT(" . self::ID . ")" .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        $sentencia->execute();

        return $sentencia->fetchColumn(0) > 0;
    }

    /**
     * Obtiene el valor de la columna "idPersona" basado en la clave de api
     * @param $claveApi
     * @return null si este no fue encontrado
     */
    private function obtenerIdPersona($claveApi)
    {
        $comando = "SELECT " . self::ID .
            " FROM " . self::NOMBRE_TABLA .
            " WHERE " . self::CLAVE_API . "=?";

        $sentencia = ConexionBD::obtenerInstancia()->obtenerBD()->prepare($comando);

        $sentencia->bindParam(1, $claveApi);

        if ($sentencia->execute()) {
            $resultado = $sentencia->fetch();
            return $resultado['idPersona'];
        } else
            return null;
    }
}

