import 'package:get/get.dart';
import 'package:uni_project/Features/Reports/Presentation/Controllers/reports_controller.dart';

class ReportsBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<ReportsController>(() => ReportsController());
  }
}
