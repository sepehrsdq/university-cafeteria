import 'package:get/get.dart';
import 'package:uni_project/Features/MainPage/Presentation/Controllers/staff_main_controller.dart';

class StaffMainPageBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<StaffMainController>(() => StaffMainController());
  }
}
