/*
CENTRAL UNIFORMES, SL
AUTOR:		JUAN FRANCISCO DOMÍNGUEZ
FECHA:		11/07/2023
DESCRIPCIÓN:	VENTAS DE LOS COMERCIALES POR MES
				RELACIÓN DE ALBARANES, LOS TICKETS QUEDAN FUERA PORQUE NO TIENEN RUTA
*/
----------------------------------------------
--RESUMEN

USE ExitERP0415
GO

DECLARE @MES AS INT
DECLARE @EJERCICIO AS INT
SET @MES = 04
SET @EJERCICIO = 2024

SELECT 
		CodigoRuta AS RUTA,
		CASE
			WHEN CodigoRuta = 91 THEN 'ROSA'
			WHEN CodigoRuta = 92 THEN 'RUBEN'
			WHEN CodigoRuta = 93 THEN 'SUSI'
		END AS COMERCIAL,
		CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO

FROM AlbaranVentaCabecera
WHERE		CodigoEmpresa = 1
		AND EjercicioAlbaran = @EJERCICIO
		AND MONTH(FechaAlbaran) = @MES
		AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta

----------------------------------------------
--VENTAS POR CLIENTE
SELECT
		CodigoRuta AS RUTA,
		CASE
			WHEN CodigoRuta = 91 THEN 'ROSA'
			WHEN CodigoRuta = 92 THEN 'RUBEN'
			WHEN CodigoRuta = 93 THEN 'SUSI'
		END AS COMERCIAL,
		CodigoCliente,
		RazonSocial,
		CAST(SUM(ImporteFactura) AS numeric(10,2)) AS FACTURADO

FROM AlbaranVentaCabecera
WHERE		CodigoEmpresa = 1
		AND EjercicioAlbaran = @EJERCICIO
		AND MONTH(FechaAlbaran) = @MES
		AND CodigoRuta IN (91,92,93)
GROUP BY CodigoRuta, CodigoCliente,RazonSocial
ORDER BY RUTA, CodigoCliente

----------------------------------------------
--VENTAS POR RUTA
SELECT 
		'Albarán' AS TIPO,
		AVC.CodigoRuta AS RUTA,
		AVC.CodigoComisionista AS COMISIONISTA,
		IIF(AVC.CodigoComisionista IN (51,3,25),COMI.Comisionista,'') AS NOMBRE,
		CONVERT(VARCHAR,AVC.FechaAlbaran,101) AS FECHA,
		AVC.CodigoCliente,
		AVC.RazonSocial,
		AVC.NumeroFactura,
		CAST(SUM(AVC.ImporteBruto) AS numeric(10,2)) AS BRUTO,
		CAST(SUM(AVC.ImporteDescuento) AS numeric(10,2)) AS DTO,
		CAST(SUM(AVC.ImporteFactura) AS numeric(10,2)) AS FACTURADO

FROM AlbaranVentaCabecera AS AVC
LEFT JOIN Comisionistas AS COMI
	ON	COMI.CodigoComisionista = AVC.CodigoComisionista
WHERE		AVC.CodigoEmpresa = 1
		AND AVC.EjercicioAlbaran = @EJERCICIO
		AND MONTH(AVC.FechaAlbaran) = @MES
		AND AVC.CodigoRuta IN (91,92,93)
GROUP BY AVC.CodigoRuta, AVC.CodigoComisionista, AVC.FechaAlbaran, AVC.CodigoCliente, AVC.RazonSocial, AVC.NumeroFactura, COMI.Comisionista
ORDER BY RUTA, AVC.FechaAlbaran, AVC.CodigoCliente

