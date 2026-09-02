-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 02-09-2026 a las 10:06:44
-- Versión del servidor: 8.0.45-36
-- Versión de PHP: 7.4.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `a0071254_pagos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empresas`
--

CREATE TABLE `empresas` (
  `id` int NOT NULL,
  `json_id` varchar(50) NOT NULL,
  `razon` varchar(255) NOT NULL,
  `cuit` varchar(20) DEFAULT NULL,
  `deuda_os` decimal(15,2) DEFAULT '0.00',
  `deuda_sindicato` decimal(15,2) DEFAULT '0.00',
  `deuda_mutual` decimal(15,2) DEFAULT '0.00',
  `observaciones` text,
  `fecha_carga` datetime DEFAULT NULL,
  `activa` tinyint(1) DEFAULT '1',
  `acuerdos` longtext
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `empresas`
--

INSERT INTO `empresas` (`id`, `json_id`, `razon`, `cuit`, `deuda_os`, `deuda_sindicato`, `deuda_mutual`, `observaciones`, `fecha_carga`, `activa`, `acuerdos`) VALUES
(2, 'emp_2ea604a40038', '4 SLAMS S.A', '30717640574', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, '{\"Obra Social\":{\"monto_total\":3000000,\"cantidad_cuotas\":6,\"monto_cuota\":500000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"acuerdo por los periodos 12/25 y 01-02-03 y 04/26\"}}'),
(5, 'emp_ca0784dd004f', 'ABDALA JOSE LUIS', '30699539453', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(8, 'emp_94d9f3c321e9', 'ACAI MENDOZA SAS', '30719062888', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(11, 'emp_555ceee3f9c7', 'ALFA PORTAL DEL VIENTO', '30717499014', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(14, 'emp_5f07e697c600', 'ALFALCA PREMIUM SAS', '30718227107', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: ALFALCA PREMIUM', '2026-06-05 14:59:41', 1, '{\"Obra Social\":{\"monto_total\":6300000,\"cantidad_cuotas\":6,\"monto_cuota\":1050000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"PAGA PERIODOS DEL 10/2023 AL 04/2026\"}}'),
(17, 'emp_3bf0f48a1da0', 'AMORE GABRIEL ANDRES', '20256283736', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: AMORE GABRIEL | Unificado también desde: GABRIEL AMORE', '2026-06-05 14:59:38', 1, NULL),
(20, 'emp_bf09c13d4c63', 'ARACENA AGUSTIN', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: AGUSTIN ARACENA | Unificado también desde: ARACENA', '2026-06-05 14:59:37', 1, '{\"Obra Social\":{\"monto_total\":18820296,\"cantidad_cuotas\":18,\"monto_cuota\":1045572,\"cuotas_pagadas_previas\":5,\"periodo_desde\":\"12/25\",\"periodo_hasta\":\"05/27\",\"observaciones\":\"HACE ACUERDO HASTA 11/25\"}}'),
(23, 'emp_15e1226c52f4', 'ASOCIACION PANADEROS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: ASOC PANADEROS', '2026-06-05 14:59:34', 1, NULL),
(26, 'emp_3aafed12e9d9', 'AYCA SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, NULL),
(29, 'emp_75530cd30753', 'BIANCO & NERO SRL', '30712085777', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: BIANCO Y NERO', '2026-06-05 14:59:37', 1, NULL),
(32, 'emp_17010e8ad384', 'BNM SA', '30716334488', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, '{\"Obra Social\":{\"monto_total\":8100000,\"cantidad_cuotas\":3,\"monto_cuota\":2700000,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"04/26\",\"periodo_hasta\":\"06/26\",\"observaciones\":\"POR LOS PERIODOS DEL 12/25 AL 03/26\"}}'),
(35, 'emp_6b6d9a20a5d0', 'BREAD ESTYS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(38, 'emp_926c823dd68b', 'BRIFFI RODRIGO', '20260553217', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: BIFFRI RODRIGO', '2026-06-05 14:59:34', 1, NULL),
(41, 'emp_23fb25808464', 'CAFÉ Y DELICATESSEN', '30715387839', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: CAFÉ Y DELICATESEN STORE', '2026-06-05 14:59:36', 1, NULL),
(44, 'emp_b7af6303c291', 'CANDEAL SRL', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:34', 1, NULL),
(47, 'emp_41a85cb94cd0', 'CAROLINA MERCADO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(50, 'emp_01fe8fc24b53', 'CARPIGIANI SAS', '30719022843', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, NULL),
(53, 'emp_2ea9047c5d46', 'CEROGRADO SAS', '30716308088', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, '{\"Obra Social\":{\"monto_total\":2850000,\"cantidad_cuotas\":4,\"monto_cuota\":715000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"09/26\",\"observaciones\":\"PAGA PERIODOS 12/25 AL 04/26\"}}'),
(56, 'emp_cc15c26fb4d9', 'CERRUTI FERNANDA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(59, 'emp_cca9a6b070df', 'CHOCOLATE SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:34', 1, NULL),
(62, 'emp_004a9ca0f9f4', 'CHOCOLEZZA SRL', '30710374860', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: CHOCOLEZZA | Unificado también desde: CHOCOLEZZA S.R.L', '2026-06-05 14:59:34', 1, NULL),
(65, 'emp_815846340418', 'CHOCOMIX SRL', '30714478857', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, '{\"Obra Social\":{\"monto_total\":3600000,\"cantidad_cuotas\":8,\"monto_cuota\":450000,\"cuotas_pagadas_previas\":7,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"12/26\",\"observaciones\":\"PERIODOS DEL 04/25 HASTA EL 03/26 CON 8 CHEQUES DE $ 450000 c/u ya entregados.\"}}'),
(68, 'emp_e07c4935cad4', 'CINTHIA NATALIA JURADO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: CINTHIA JURADO | Unificado también desde: JURADO CINTHIA NATALIA | Unificado también desde: NATALIA JURADO', '2026-06-05 14:59:34', 1, NULL),
(71, 'emp_09d45ada4fa4', 'CISNOVAL S.A', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(74, 'emp_e063db01ec84', 'CLAUDIA PELAYES ALICIA', '27208105162', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, '[]'),
(77, 'emp_9171948a4c65', 'EL CLUB DEL CAFÉ', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(80, 'emp_66ec7e794e7d', 'CORDILLERA GASTRONOMIA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(83, 'emp_55d12545f6ed', 'CORONEL ZONA ESTE', '30718944984', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, '{\"Obra Social\":{\"monto_total\":3059753.5,\"cantidad_cuotas\":4,\"monto_cuota\":764938.37,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"por los periodos del 10 al 12/25 y del 01 al 03/26\"}}'),
(86, 'emp_9dffddb417ae', 'CUYO CREAM LOMORO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(89, 'emp_623c83f05261', 'CUYOCREM ARG S.A', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(92, 'emp_5c759d812f7b', 'DACO & DACA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(95, 'emp_ef8439b0f7c6', 'DEZE SAS', '30715742434', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(98, 'emp_9103fb99bafb', 'DOLPHIN', '30707497277', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:41', 1, NULL),
(101, 'emp_b05441835083', 'DULCES TENTACIONES', '30718059255', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:34', 1, NULL),
(104, 'emp_b7918ec1aedb', 'ESMINI SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(107, 'emp_fd388c3b23ac', 'FAST FOOD SUD SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: FAST FOOD SUDAM', '2026-06-05 14:59:34', 1, NULL),
(110, 'emp_1cd91b15a907', 'FERRARA HORACIO', '20169906999', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: FERRARA', '2026-06-05 14:59:42', 1, '{\"Obra Social\":{\"monto_total\":435000,\"cantidad_cuotas\":3,\"monto_cuota\":145000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"04/26\",\"periodo_hasta\":\"06/26\",\"observaciones\":\"PAGA PERIODOS DEL 11/25 AL 02/26\"}}'),
(113, 'emp_efc74a3f0f37', 'FETTICHE SRL', '30715689304', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, '{\"Obra Social\":{\"monto_total\":746615.63,\"cantidad_cuotas\":3,\"monto_cuota\":253351.9,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"07/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS 04/05/06/07/08/09/10/11/12/2025 Y 01/02/03/2026\"}}'),
(116, 'emp_6b630486004a', 'FIDEICOMISO DE OPERADORES', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: FIDEICOMISO OPE | Unificado también desde: FIDEICOMISO OPERAD | Unificado también desde: FIDEICOMISO OPERADOR', '2026-06-05 14:59:39', 1, NULL),
(119, 'emp_e268d0cd6b0f', 'FLORENCIA RUBI OVEJERO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(122, 'emp_2fb02018b71f', 'FRESCO Y NATURAL', '30718555104', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, '{\"Obra Social\":{\"monto_total\":4200000,\"cantidad_cuotas\":14,\"monto_cuota\":300000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/25\",\"periodo_hasta\":\"07/26\",\"observaciones\":\"PAGA DEUDA OBRA SOCIAL HASTA 04/25\"}}'),
(125, 'emp_85ddda379b71', 'FUCILI ESTEFANIA', '27328073957', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, '{\"Obra Social\":{\"monto_total\":570000,\"cantidad_cuotas\":3,\"monto_cuota\":190000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"07/26\",\"observaciones\":\"PAGA PERIODOS DEL 11/25 AL 03/26\"}}'),
(128, 'emp_6385f83eb73a', 'FUCILI NATALIA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(131, 'emp_bf1e7dc9c3eb', 'FYCYP S.A.S', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(134, 'emp_cc297465445a', 'GALLARDO ADRIANA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(137, 'emp_56d4293907db', 'GARCIA DIEGO ROBERTO', '20263399456', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(140, 'emp_cf7512585ad5', 'GASTRO SUD SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: GASTRO SUD', '2026-06-05 14:59:35', 1, NULL),
(143, 'emp_743c4d9c7c50', 'GATICA VIVIANA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(146, 'emp_0ec2957416cf', 'GBL SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: G.B.L SAS', '2026-06-05 14:59:39', 1, NULL),
(149, 'emp_44de83b334f5', 'GCG LUMAJ SRL', '30715782770', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: CGC LUMAJ | Unificado también desde: G.C.G LUMAJ S.R.L | Unificado también desde: GCG LUMAJ', '2026-06-05 14:59:35', 1, '{\"Obra Social\":{\"monto_total\":5360000,\"cantidad_cuotas\":6,\"monto_cuota\":895000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"PAGA PERIODOS 09/25 AL 04/26\"}}'),
(152, 'emp_9fb7dd34ec23', 'GENFLIAR SAS', '30718325532', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: GENFLIAR S.A.S', '2026-06-05 14:59:35', 1, '[]'),
(155, 'emp_6f4560ce5783', 'GIUSEPPA SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, '{\"Obra Social\":{\"monto_total\":5255650,\"cantidad_cuotas\":10,\"monto_cuota\":525565,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"02/27\",\"observaciones\":\"PAGA PERIODOS DEL 04/25 AL 02/26\"}}'),
(158, 'emp_d681267c1f9d', 'GROSSA MORA SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: GROSA MORA', '2026-06-05 14:59:35', 1, NULL),
(161, 'emp_65e8ae3672a5', 'GUAJARDO ELINA', '27111800567', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, '[]'),
(164, 'emp_2a1539ca8080', 'GUIÑAZU MARIO DARIO', '20264632871', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, '[]'),
(167, 'emp_d85993f8c7a1', 'GUIÑAZU NATALIA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(170, 'emp_51313d67a80c', 'HELA CORDON DEL PLATA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, NULL),
(173, 'emp_03a431fdac35', 'ITALO GERARDO DAVID', '20130061835', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: DAVID GERARDO ITALO | Unificado también desde: ITALO GERADO DAVID', '2026-06-05 14:59:35', 1, NULL),
(176, 'emp_b004074ae381', 'ITALO PABLO DAVID', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(179, 'emp_6eddc8db5dbc', 'JALIL ANDRES', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(182, 'emp_4cab17150c9e', 'JANTANO HUGO ALBERTO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(185, 'emp_1d268918c75c', 'JUAN MARTINEZ', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(188, 'emp_065cb3fa6f6d', 'LAGADI SA', '3071787071', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, '{\"Obra Social\":{\"monto_total\":9540000,\"cantidad_cuotas\":9,\"monto_cuota\":1060000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"01/27\",\"observaciones\":\"PAGA PERIODOS 02/25 AL 02/26\"}}'),
(191, 'emp_d6f090747b8e', 'LAYBA SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(194, 'emp_e5b932192100', 'LEVURE SAS', '30-71803640-9', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: LEVURE S.A.S', '2026-06-05 14:59:35', 1, NULL),
(197, 'emp_9622e030781b', 'LILIANA GODOY', '27242323947', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: GODOY LILIANA', '2026-06-05 14:59:33', 1, '{\"Obra Social\":{\"monto_total\":11794398.84,\"cantidad_cuotas\":12,\"monto_cuota\":982867,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"04/26\",\"periodo_hasta\":\"03/27\",\"observaciones\":\"paga periodos del 02/25 al 03/26\"}}'),
(200, 'emp_8bfdb2d74877', 'LUIS SOPPELSA AUGUSTO', '20285112894', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, '[]'),
(203, 'emp_2bfcb49a3a42', 'MACKENZIE', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(206, 'emp_33df34fa0a0c', 'MAILHO SA', '30709166537', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: MAILHO', '2026-06-05 14:59:43', 1, NULL),
(209, 'emp_bc314b356b26', 'MAJESTAD SA', '30711545235', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, '{\"Obra Social\":{\"monto_total\":18300000,\"cantidad_cuotas\":15,\"monto_cuota\":1220000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"08/27\",\"observaciones\":\"PAGA PERIODOS 05/25 AL 04/26\"}}'),
(212, 'emp_d9d16a654a62', 'MARGARITA BAJINAY', '23248216174', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, NULL),
(215, 'emp_913bd5dc6709', 'MERLETTI LIDIA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: LIDIA MARLETI', '2026-06-05 14:59:42', 1, NULL),
(218, 'emp_54ce05d77dd1', 'MIRTHA SAEZ', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, NULL),
(221, 'emp_fd6fd9eaaf93', 'MONREAL HERMANOS', '33716093099', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(224, 'emp_61a560cf1f0c', 'MOYANO ERNESTO', '20303439634', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(227, 'emp_60edcea4bb14', 'NAPOLEON 1981', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(230, 'emp_b0ed1ab69cea', 'NAVESI MAXIMO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(233, 'emp_dc188420b3ac', 'NAVESI RAUL', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: NAVESI RAUL ROCA 316', '2026-06-05 14:59:35', 1, NULL),
(236, 'emp_f57cd2ab2bf8', 'CAMPILLAY NERINA VANESA', '27253272843', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: NERINA CAPILLAY', '2026-06-05 14:59:42', 1, '{\"Obra Social\":{\"monto_total\":400000,\"cantidad_cuotas\":10,\"monto_cuota\":40000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"03/27\",\"observaciones\":\"paga periodos desde 10/25 al 03/26\"}}'),
(239, 'emp_cca9e9a7f97b', 'NICE FOOD SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(242, 'emp_4143cb10245f', 'NIV VIL ARG SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, NULL),
(245, 'emp_64c4e85715a3', 'NUSS NAVESI AGUS ALV', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: NUSS AGUS ALV', '2026-06-05 14:59:35', 1, NULL),
(248, 'emp_71171f06cad1', 'NUSS SA ROCA 316', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:35', 1, NULL),
(251, 'emp_e84e9251f29d', 'OROS SEBASTIAN', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, NULL),
(254, 'emp_c29dfbf22803', 'PABLO JAVIER SCIFO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: PABLO SCIFO', '2026-06-05 14:59:35', 1, NULL),
(257, 'emp_fed9114c750c', 'EL PALACIO DE LA ENPAN', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:33', 1, NULL),
(260, 'emp_f6f988546ff0', 'PALAMA SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: PLAMALA SAS', '2026-06-05 14:59:39', 1, NULL),
(263, 'emp_afb5bab5288c', 'PANADERIA AMANECER', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(266, 'emp_d19a6a5fc964', 'PANADERIA CERVANTES', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(269, 'emp_de6535e2397a', 'PANADERIA MIA SAS', '30718357558', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: PANADERIA MIA', '2026-06-05 14:59:35', 1, '[]'),
(272, 'emp_ec2ff586cd47', 'PANADERIA NUSS SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: PANADERIA NUSS MARIA', '2026-06-05 14:59:42', 1, NULL),
(275, 'emp_2af4746c3191', 'PANADERIA TRIGAL', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(278, 'emp_9d0a097babe9', 'PASPAN SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: PAN PAN | Unificado también desde: PANADERIA PAS PAN', '2026-06-05 14:59:35', 1, '{\"Obra Social\":{\"monto_total\":920000,\"cantidad_cuotas\":3,\"monto_cuota\":316738,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"07/26\",\"observaciones\":\"PAGA PERIODO 04/26\"}}'),
(281, 'emp_16fd9c2fd974', 'PATERNITTE PABLO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, NULL),
(284, 'emp_26ef4b281832', 'PERIN EMPRENDIEMNTOS SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:39', 1, NULL),
(287, 'emp_65c7e3bfd149', 'PETIT PASTELERIA', '33717044539', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(290, 'emp_bbc545f784e7', 'PITARO SANDRA', '27262957247', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(293, 'emp_e57b6e65fc27', 'PIZERIA POPULAR SL', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(296, 'emp_e4e2e1b1b84c', 'QUEBEC SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(299, 'emp_c7e160da6300', 'QUEEN ENERGIA SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: QUEEN ENERGIA', '2026-06-05 14:59:36', 1, NULL),
(302, 'emp_a49b710607eb', 'RAUMAN SAS', '30718231112', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, '{\"Obra Social\":{\"monto_total\":1160000,\"cantidad_cuotas\":2,\"monto_cuota\":580000,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"06/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS 12/2025- 01/02/03/04/2026\"}}'),
(305, 'emp_4d7d56d9caad', 'REIKIAVIK SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:42', 1, '{\"Sindicato\":{\"monto_total\":2270000,\"cantidad_cuotas\":5,\"monto_cuota\":454000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"PERIODOS 12/24 AL 03/26\"},\"Mutual\":{\"monto_total\":1130000,\"cantidad_cuotas\":5,\"monto_cuota\":226000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"\"}}'),
(308, 'emp_0b272cba1bd9', 'SANCHO FAST FOOD', '30718920511', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:43', 1, NULL),
(311, 'emp_e1d09b0fad77', 'SARAVIA GABRIELA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: GABRIELA SARAVIA', '2026-06-05 14:59:43', 1, NULL),
(314, 'emp_a735dd2c9bfd', 'SE FELIZ SRL', '30715965158', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: SE FELIZ | Unificado también desde: SÉ FELÍZ', '2026-06-05 14:59:37', 1, NULL),
(317, 'emp_d288f4009d79', 'SEPTD SRL', '30717576639', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:37', 1, '{\"Obra Social\":{\"monto_total\":3000000,\"cantidad_cuotas\":3,\"monto_cuota\":1000000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"PAGA PERIODOS DESDE 12/25 AL 04/26\"}}'),
(320, 'emp_5f64ef41792e', 'SILVA ALFREDO RUBEN', '20141749863', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:43', 1, '[]'),
(323, 'emp_a8c347ff87db', 'SOFIA CAFÉ', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, NULL),
(326, 'emp_8c49c0ba72a9', 'SOLUCIONES RANDSTAD', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:43', 1, NULL),
(329, 'emp_40e199c87932', 'SOSPE SAS', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(332, 'emp_667e46398494', 'SOTELO EDUARDO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(335, 'emp_96499a4af5e2', 'STRAWBERRY SAS', '30717611337', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:36', 1, '{\"Obra Social\":{\"monto_total\":1734612.78,\"cantidad_cuotas\":6,\"monto_cuota\":289102.13,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"10/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS 12/24 - 01/02/03/ 2025 - 05/06/07/08/2025\"}}'),
(338, 'emp_af84e09d4063', 'TAGLIA & CO SA', '30718143833', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: TAGLIA & CO S,A | Unificado también desde: TAGLIA&CO | Unificado también desde: TAGLIACO SA', '2026-06-05 14:59:39', 1, '{\"Obra Social\":{\"monto_total\":9336278.09,\"cantidad_cuotas\":4,\"monto_cuota\":2334069.52,\"cuotas_pagadas_previas\":3,\"periodo_desde\":\"03/26\",\"periodo_hasta\":\"06/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS- 07/08/09/10/11/12/ 2025\"}}'),
(341, 'emp_5daac551598f', 'TAPIA IL MONDO GELATO', '30717808882', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(344, 'emp_40cd75377a0c', 'VIDAL ROCIO', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm', '2026-06-05 14:59:38', 1, NULL),
(347, 'emp_4cc78064c0dd', 'VILÑEZ SA', '', 0.00, 0.00, 0.00, 'Importado desde Excel LISTADO DE COBRANZAS(1).xlsm | Unificado también desde: VIL-ÑEZ SA | Unificado también desde: VILÑEZ', '2026-06-05 14:59:43', 1, '{\"Sindicato\":{\"monto_total\":3987500,\"cantidad_cuotas\":8,\"monto_cuota\":498437.5,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"01/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"PAGA DEUDA SINDICAL HASTA 12/25\"},\"Mutual\":{\"monto_total\":3987500,\"cantidad_cuotas\":8,\"monto_cuota\":498437.5,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"01/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"PAGA PERIODOS HASTA 12/25\"}}'),
(350, 'emp_6a26db5d1a971', 'MANZANO MARCELO GUSTAVO', '20-23942898-4', 0.00, 0.00, 0.00, '', '2026-06-08 12:10:21', 1, NULL),
(353, 'emp_6a26dbd4315da', 'PANIFICADORA SAN JOSE SAS', '30716680106', 0.00, 0.00, 0.00, '', '2026-06-08 12:12:20', 1, '{\"Obra Social\":{\"monto_total\":4950000,\"cantidad_cuotas\":9,\"monto_cuota\":550000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"02/27\",\"observaciones\":\"\"}}'),
(356, 'emp_6a26e5087518e', 'JEZREEL SAS', '30718694759', 0.00, 0.00, 0.00, '', '2026-06-08 12:51:36', 1, '{\"Obra Social\":{\"monto_total\":1631523.72,\"cantidad_cuotas\":4,\"monto_cuota\":407881,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"09/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS DEL 03/25 AL 04/26\"}}'),
(359, 'emp_6a26e5ebcff03', 'OLMEDO ROXANA NELIDA', '27224075966', 0.00, 0.00, 0.00, '', '2026-06-08 12:55:23', 1, '{\"Obra Social\":{\"monto_total\":3660000,\"cantidad_cuotas\":6,\"monto_cuota\":610000,\"cuotas_pagadas_previas\":2,\"periodo_desde\":\"03/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"ACUERDO DE PAGO POR LOS PERIODOS DEL 09 AL 12/24 Y DEL 01 AL 07/25 Y DEL 09 AL 12/25 Y 01/02 DEL 2026\"}}'),
(362, 'emp_6a26e643dfab6', 'SOCIEDAD DE HECHO DE SALTALEGIO', '30711858349', 0.00, 0.00, 0.00, '', '2026-06-08 12:56:51', 1, '{\"Obra Social\":{\"monto_total\":3600000,\"cantidad_cuotas\":4,\"monto_cuota\":900000,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"09/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS 11/2025 Y 01/02/03/04 DEL 2026 PAGADOS CON 4 CHEQUES CON VTO EL 11/06/26 - 27/06/26 - 12/07/26 - 27/07/26\"}}'),
(365, 'emp_6a26f6fb85dd5', 'BARONEGRO S.A.S.', '30-71729880-9', 0.00, 0.00, 0.00, 'HELADERIA 273/96', '2026-06-08 14:08:11', 1, NULL),
(368, 'emp_6a27fbbbe84fa', 'LUVIC S.A', '30716219611', 0.00, 0.00, 0.00, 'KINGO AVENIDA LAS HERAS CCT 329/2000', '2026-06-09 08:40:43', 1, '{\"Obra Social\":{\"monto_total\":22100000,\"cantidad_cuotas\":34,\"monto_cuota\":650000,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"05/26\",\"periodo_hasta\":\"03/29\",\"observaciones\":\"ACUERDO POR LOS PERIODOS 12/23 AL 03/26\"}}'),
(371, 'emp_6a28056606fc6', 'CARLOS PINTO PEDROSA', '20-28527243-3', 0.00, 0.00, 0.00, 'CHOCOLATERIA 384/27', '2026-06-09 09:21:58', 1, NULL),
(374, 'emp_6a2806152a80e', 'ECHENIQUE ANA MARIA', '27-11298459-9', 0.00, 0.00, 0.00, 'PIZZERIA CCT 384/75', '2026-06-09 09:24:53', 1, NULL),
(377, 'emp_6a280fc2602e9', 'CINOSVAL S.A.', '33-71824821-9', 0.00, 0.00, 0.00, 'GALLETERIA CCT 384/75', '2026-06-09 10:06:10', 1, NULL),
(380, 'emp_6a2812dec4a07', 'NECULQUEO GALLEGOS ANDRES', '23-92615262-9', 0.00, 0.00, 0.00, 'ALFAJORES 384/75', '2026-06-09 10:19:26', 1, '{\"Obra Social\":{\"monto_total\":1791000,\"cantidad_cuotas\":3,\"monto_cuota\":597000,\"cuotas_pagadas_previas\":0,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"ACUERDO POR LOS PERIODOS DESDE 10/25 AL 04/26\"}}'),
(383, 'emp_6a28146f5c78f', 'DULCI SUPREME S.A.S.', '30-71833702-6', 0.00, 0.00, 0.00, 'HELADERIA 273/96', '2026-06-09 10:26:07', 1, '{\"Obra Social\":{\"monto_total\":330000,\"cantidad_cuotas\":3,\"monto_cuota\":110000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"04/26\",\"periodo_hasta\":\"06/26\",\"observaciones\":\"PAGA DEUDA OBRA SOCIAL 01 AL 03/26\"}}'),
(386, 'emp_6a2996081eaf2', 'discos romi s.a.s', '30717525201', 0.00, 0.00, 0.00, '', '2026-06-10 13:51:20', 1, NULL),
(389, 'emp_6a2a999c6cb5e', 'ABALOS PABLO ARIEL', '20356231040', 0.00, 0.00, 0.00, '', '2026-06-11 08:18:52', 1, '{\"Obra Social\":{\"monto_total\":3288244.76,\"cantidad_cuotas\":10,\"monto_cuota\":328824.47,\"cuotas_pagadas_previas\":1,\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"03/27\",\"observaciones\":\"ACUERDO POR LOS PERIODOS, 01/02/03/04/05/07/08/09/10/11/12/2024 - 01/02/03/04/2025 - 02/03/2026\"}}'),
(392, 'emp_6a2c0535c8001', 'NUEVA GESTION S.A.S.', '30-71811112-5', 0.00, 0.00, 0.00, '', '2026-06-12 10:10:13', 1, '{\"Sindicato\":{\"monto_total\":267000,\"cantidad_cuotas\":2,\"monto_cuota\":133500,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"04/26\",\"periodo_hasta\":\"05/26\",\"observaciones\":\"PAGA PERIODOS 07/25 AL 04/26\"}}'),
(395, 'emp_6a2c2348b2664', 'DE IPOLA CARLOS', '20135339769', 0.00, 0.00, 0.00, '', '2026-06-12 12:18:32', 1, NULL),
(398, 'emp_6a2c244608f9e', 'PANIFICADORA CERVANTES SRL', '30642680605', 0.00, 0.00, 0.00, '', '2026-06-12 12:22:46', 1, NULL),
(401, 'emp_6a2c287b46c07', 'LAS DELICIAS SA', '30711459142', 0.00, 0.00, 0.00, '', '2026-06-12 12:40:43', 1, NULL),
(404, 'emp_6a31501ee88c2', 'purisito', '20564905732', 0.00, 0.00, 0.00, '', '2026-06-16 10:31:10', 0, NULL),
(407, 'emp_6a3287347a677', 'PANADERIA BON PAN', '30683149299', 0.00, 0.00, 0.00, '', '2026-06-17 08:38:28', 1, NULL),
(410, 'emp_6a341a36832dc', 'JERAL MOSTAZA S.A.S', '30717790649', 0.00, 0.00, 0.00, '', '2026-06-18 13:17:58', 1, NULL),
(413, 'emp_6a341a8a6fb6d', 'ENTRE DOS S.R.L', '30711684901', 0.00, 0.00, 0.00, '', '2026-06-18 13:19:22', 1, NULL),
(416, 'emp_6a357c1258846', 'ROSALIA VERONICA HEREDIA', '27-24745092-6', 0.00, 0.00, 0.00, '', '2026-06-19 14:27:46', 1, '{\"Obra Social\":{\"monto_total\":385000,\"cantidad_cuotas\":2,\"monto_cuota\":192500,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"07/26\",\"observaciones\":\"PAGA PERIODOS 02, 03, 12/25 Y 02/26\"}}'),
(419, 'emp_6a392e0e5810a', 'BARRERA PABLO JESUS', '20267479160', 0.00, 0.00, 0.00, '', '2026-06-22 09:43:58', 1, NULL),
(422, 'emp_6a39382b63471', 'MENDOWAY SRL', '30715319884', 0.00, 0.00, 0.00, '', '2026-06-22 10:27:07', 1, NULL),
(425, 'emp_6a393a8396776', 'AHMAD GABRIELA SILVANA', '27174412885', 0.00, 0.00, 0.00, '', '2026-06-22 10:37:07', 1, NULL),
(428, 'emp_6a3aa19ab635a', 'LAOUN GALINDO S.A.S.', '30-63896336-8', 0.00, 0.00, 0.00, 'CCT 384/75 LA VENE', '2026-06-23 12:09:14', 1, '{\"Obra Social\":{\"monto_total\":816000,\"cantidad_cuotas\":3,\"monto_cuota\":272000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"08/26\",\"observaciones\":\"PAGA PERIODOS 01 AL 05/2026\"}}'),
(431, 'emp_6a3aa1cb9a9c4', 'SIMA CUYO S.A.S.', '30-71821321-1', 0.00, 0.00, 0.00, 'CCT 384/75 LA VENE', '2026-06-23 12:10:03', 1, '{\"Obra Social\":{\"monto_total\":2910000,\"cantidad_cuotas\":6,\"monto_cuota\":485000,\"cuotas_pagadas_previas\":0,\"pagos_previos_ids\":[],\"periodo_desde\":\"06/26\",\"periodo_hasta\":\"11/26\",\"observaciones\":\"PÁGA PERIODOS 11 Y 12/2025 Y DEL 01 AL 05/2026\"}}');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_json_id` (`json_id`),
  ADD KEY `idx_cuit` (`cuit`),
  ADD KEY `idx_razon` (`razon`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=432;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
