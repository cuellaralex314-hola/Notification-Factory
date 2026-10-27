<?php

class OrderService
{
    public function __construct(
        private PriceCalculator $calculadora,
        private NotificationSender $notificador,
        private OrderEvents $eventos,
        private ReportGenerator $generadorDeReportes
    ) {
    }

    public function procesarPedidoCompleto(
        int $id,
        string $paciente,
        float $monto,
        string $tipoPaciente,
        string $tipoNotificacion,
        string $destino
    ): array {
        $this->validarPedido($paciente, $monto);
        $total = $this->calculadora->calcular($monto, $tipoPaciente);
        $order = new Order($id, $paciente, $total, $tipoPaciente);
        $order->guardar();
        $this->notificador->enviar($tipoNotificacion, $destino, "Pedido {$id} por $ {$total}");
        $this->eventos->pedidoCreado($order);

        return [
            'id' => $id,
            'paciente' => $paciente,
            'total' => $total,
            'reporte' => $this->generadorDeReportes->generate("Pedido {$id}", true, true, false),
        ];
    }

    private function validarPedido(string $paciente, float $monto): void
    {
        if (trim($paciente) === '') {
            throw new InvalidArgumentException('Falta el paciente.');
        }

        if ($monto <= 0) {
            throw new InvalidArgumentException('El monto debe ser mayor que cero.');
        }
    }
}
