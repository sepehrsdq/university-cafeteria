import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/staff_tickets_controller.dart';

class StaffTicketsBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<StaffTicketsController>(() => StaffTicketsController());
  }
}
