import 'package:get/get.dart';
import 'package:uni_project/Features/Tickets/Presentation/Controllers/tickets_controller.dart';

class TicketsBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<TicketsController>(() => TicketsController());
  }
}
