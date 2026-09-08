<?php

namespace RcnSistemas\Controllers\Admin;

use InvalidArgumentException;
use RcnSistemas\Core\Controller;
use RcnSistemas\Core\Http\Request;
use RcnSistemas\Core\Http\Response;
use RcnSistemas\Core\View\ViewRenderer;
use RcnSistemas\Repositories\CustomerRepository;
use RcnSistemas\Repositories\TicketRepository;
use RcnSistemas\Repositories\TicketDetailRepository;
use RcnSistemas\Repositories\CustomerSearchRepository;
use RcnSistemas\Repositories\AdminUserManagementRepository;
use RcnSistemas\Services\Customers\CustomerIdentity;
use RcnSistemas\Services\Customers\CustomerService;
use RcnSistemas\Services\Tickets\TicketService;
use RcnSistemas\Services\Tickets\TicketWorkflow;
use RcnSistemas\Services\Tickets\TicketScheduleService;
use RcnSistemas\Services\Tickets\TicketPricing;
use RcnSistemas\Services\Tickets\TicketAssignmentService;
use RcnSistemas\Services\Communications\UploadService;

final class TicketController extends Controller
{
    public function __construct(private readonly TicketRepository $tickets, private readonly CustomerRepository $customers, ViewRenderer $views, private readonly ?TicketDetailRepository $details = null, private readonly ?CustomerSearchRepository $customerSearch = null, private readonly ?UploadService $uploads = null, private readonly string $uploadDirectory = '', private readonly ?AdminUserManagementRepository $users = null) { parent::__construct($views); }

    public function create(Request $request, int $viewerId, string $viewerRole = 'agent'): Response
    {
        $customerId = (int) $request->query('customer_id', 0);
        $customerMode = $customerId > 0 ? 'existing' : (string) $request->query('customer_mode', 'new');
        if (!in_array($customerMode, ['existing', 'new'], true)) $customerMode = 'new';
        $error = null; $values = ['customer_id' => $customerId, 'name' => '', 'phone' => '', 'email' => '', 'business_name' => '', 'customer_type' => 'persona_fisica', 'assigned_user_id' => $viewerId, 'consultation_type' => 'Soporte', 'status' => 'Nuevo', 'subject' => '', 'context_label' => '', 'price_amount' => '', 'appointment_date' => '', 'appointment_time' => '', 'service_date' => '', 'service_start_time' => '', 'service_end_time' => '', 'message' => ''];
        if ($request->isMethod('POST')) {
            foreach ($values as $key => $value) $values[$key] = $request->input($key, $value);
            $customerMode = in_array($request->input('customer_mode', 'new'), ['existing', 'new'], true) ? $request->input('customer_mode', 'new') : 'new';
            try {
                $customerId = (int) $values['customer_id'];
                if ($customerMode === 'existing' && $customerId <= 0) throw new InvalidArgumentException('Selecciona un cliente existente.');
                if ($customerId > 0 && !$this->customers->find($customerId)) throw new InvalidArgumentException('El cliente seleccionado no existe.');
                $customer = $customerId > 0 ? $this->customers->find($customerId) : (new CustomerService($this->customers, new CustomerIdentity()))->save(['name' => $values['name'], 'phone' => $values['phone'], 'email' => $values['email'], 'business_name' => $values['business_name'], 'customer_type' => $values['customer_type']]);
                $customerId = $customer->id;
                $assignedUserId = $viewerId;
                if ($this->users && (int) $values['assigned_user_id'] > 0) {
                    $requestedUser = $this->users->findById((int) $values['assigned_user_id']);
                    if ($requestedUser && (int) $requestedUser['active'] === 1) {
                        $assignedUserId = $viewerRole === 'admin' ? (int) $requestedUser['id'] : $viewerId;
                    }
                }
                $price = null;
                if (trim((string) $values['price_amount']) !== '') $price = (new TicketPricing())->calculate((float) $values['price_amount'], $customer->customerType);
                if ($values['appointment_date'] !== '' || $values['appointment_time'] !== '') (new TicketScheduleService())->validateAppointment((string) $values['appointment_date'], (string) $values['appointment_time']);
                if ($values['service_date'] !== '' || $values['service_start_time'] !== '' || $values['service_end_time'] !== '') (new TicketScheduleService())->durationMinutes((string) $values['service_start_time'], (string) $values['service_end_time']);
                $file = $request->file('attachment');
                $storedAttachment = $file && $this->uploads && $this->uploadDirectory !== '' ? $this->uploads->store($file, $this->uploadDirectory) : '';
                $ticket = (new TicketService($this->tickets, new TicketWorkflow()))->create(['customer_id' => $customerId, 'consultation_type' => $values['consultation_type'], 'status' => $values['status'], 'subject' => $values['subject'], 'context_label' => $values['context_label'], 'message' => $values['message'], 'origin_type' => 'admin', 'assigned_user_id' => $assignedUserId, 'assigned_by_user_id' => $viewerId, 'price' => $price, 'appointment_date' => $values['appointment_date'], 'appointment_time' => $values['appointment_time'], 'service_date' => $values['service_date'], 'service_start_time' => $values['service_start_time'], 'service_end_time' => $values['service_end_time'], 'attachment_path' => $storedAttachment ? 'uploads/tickets/' . $storedAttachment : null]);
                return Response::redirect('ticket_view.php?id=' . $ticket->id);
            } catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        }
        $customerQuery = trim((string) $request->query('customer_query', ''));
        $customerResults = $this->customerSearch?->search($customerQuery) ?? [];
        $selectedCustomer = $customerId > 0 ? $this->customers->find($customerId) : null;
        return $this->view('admin.tickets.create', ['pageTitle' => 'Nuevo ticket', 'values' => $values, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'customerQuery' => $customerQuery, 'customerResults' => $customerResults, 'selectedCustomer' => $selectedCustomer, 'assignableUsers' => $this->users?->all() ?? [], 'viewerRole' => $viewerRole, 'customerMode' => $customerMode], 'layouts.admin');
    }

    public function show(Request $request, int $viewerId, string $viewerRole): Response
    {
        $ticket = $this->tickets->find((int) $request->query('id', 0));
        if (!$ticket || ($viewerRole !== 'admin' && $ticket->assignedUserId !== $viewerId)) return $this->view('errors.404', [], 'layouts.admin', 404);
        $returnUrl = $this->returnUrl((string) $request->query('return', $request->input('return', 'tickets.php')));
        $error = null;
        if ($request->isMethod('POST')) {
            try {
                $action = (string) $request->input('action', 'status');
                if ($action === 'observation' && $this->details) { $content = (new \RcnSistemas\Services\Tickets\TicketObservationService())->validate((string) $request->input('content', '')); if ($ticket->status === 'Cerrado' || !$this->details->addObservation($ticket->id, $content, $viewerId)) throw new InvalidArgumentException('No se pudo guardar la observación.'); }
                elseif ($action === 'message' && $this->details) { $message = trim((string) $request->input('message', '')); if ($message === '' || !$this->details->updateMessage($ticket->id, $message, $viewerId)) throw new InvalidArgumentException('No se pudo actualizar el mensaje.'); }
                elseif ($action === 'price') { $price = (new TicketPricing())->calculate((float) $request->input('price_amount', 0), $ticket->customerType ?: 'persona_fisica'); if (!$this->tickets->updatePrice($ticket->id, $price, $viewerId)) throw new InvalidArgumentException('No se pudo actualizar el precio.'); }
                elseif ($action === 'assignee') { $assignedUserId = (int) $request->input('assigned_user_id', 0); (new TicketAssignmentService())->validate($viewerRole, $assignedUserId); $requestedUser = $this->users?->findById($assignedUserId); if (!$requestedUser || (int) $requestedUser['active'] !== 1) throw new InvalidArgumentException('El responsable seleccionado no está disponible.'); if (!$this->tickets->updateAssignee($ticket->id, $assignedUserId, $viewerId)) throw new InvalidArgumentException('No se pudo actualizar el responsable.'); if ($viewerRole !== 'admin' && $assignedUserId !== $viewerId) return Response::redirect($returnUrl); }
                elseif ($action === 'appointment') { $appointmentDate = trim((string) $request->input('appointment_date', '')); $appointmentTime = trim((string) $request->input('appointment_time', '')); if (($appointmentDate !== '') xor ($appointmentTime !== '')) throw new InvalidArgumentException('La fecha y hora de la cita deben capturarse juntas.'); if ($appointmentDate !== '') (new TicketScheduleService())->validateAppointment($appointmentDate, $appointmentTime); if (!$this->tickets->updateSchedule($ticket->id, $appointmentDate ?: null, $appointmentTime ?: null, $ticket->serviceDate, $ticket->serviceStartTime, $ticket->serviceEndTime)) throw new InvalidArgumentException('No se pudo actualizar la cita.'); }
                elseif ($action === 'service') { $serviceDate = trim((string) $request->input('service_date', '')); $serviceStart = trim((string) $request->input('service_start_time', '')); $serviceEnd = trim((string) $request->input('service_end_time', '')); if (($serviceStart !== '') xor ($serviceEnd !== '')) throw new InvalidArgumentException('El inicio y fin del servicio deben capturarse juntos.'); if ($serviceStart !== '') (new TicketScheduleService())->durationMinutes($serviceStart, $serviceEnd); if (!$this->tickets->updateSchedule($ticket->id, $ticket->appointmentDate, $ticket->appointmentTime, $serviceDate ?: null, $serviceStart ?: null, $serviceEnd ?: null)) throw new InvalidArgumentException('No se pudo actualizar la ejecución del servicio.'); }
                elseif ($action === 'schedule') { $appointmentDate = trim((string) $request->input('appointment_date', '')); $appointmentTime = trim((string) $request->input('appointment_time', '')); $serviceDate = trim((string) $request->input('service_date', '')); $serviceStart = trim((string) $request->input('service_start_time', '')); $serviceEnd = trim((string) $request->input('service_end_time', '')); if (($appointmentDate !== '') xor ($appointmentTime !== '')) throw new InvalidArgumentException('La fecha y hora de la cita deben capturarse juntas.'); if (($serviceStart !== '') xor ($serviceEnd !== '')) throw new InvalidArgumentException('El inicio y fin del servicio deben capturarse juntos.'); if ($appointmentDate !== '') (new TicketScheduleService())->validateAppointment($appointmentDate, $appointmentTime); if ($serviceStart !== '') (new TicketScheduleService())->durationMinutes($serviceStart, $serviceEnd); if (!$this->tickets->updateSchedule($ticket->id, $appointmentDate ?: null, $appointmentTime ?: null, $serviceDate ?: null, $serviceStart ?: null, $serviceEnd ?: null)) throw new InvalidArgumentException('No se pudo actualizar la agenda.'); }
                else (new TicketService($this->tickets, new TicketWorkflow()))->changeStatus($ticket, (string) $request->input('status', ''));
                return Response::redirect('ticket_view.php?id=' . $ticket->id . '&return=' . urlencode($returnUrl));
            }
            catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
        }
        return $this->view('admin.tickets.show', ['pageTitle' => 'Ticket #' . $ticket->id, 'ticket' => $ticket, 'error' => $error, 'csrf' => htmlspecialchars((string) ($_SESSION['_csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'), 'statuses' => (new TicketWorkflow())->statuses(), 'observations' => $this->details?->observations($ticket->id) ?? [], 'assignableUsers' => $this->users?->all() ?? [], 'viewerRole' => $viewerRole, 'returnUrl' => $returnUrl], 'layouts.admin');
    }

    private function returnUrl(string $url): string
    {
        $url = trim($url);

        return preg_match('#^(tickets|notifications|customer_view)\\.php(?:\\?[^\\r\\n]*)?$#', $url) ? $url : 'tickets.php';
    }
}
