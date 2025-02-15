import 'package:get/get.dart';
import 'package:uni_project/Features/Lunch/Presentation/Controllers/lunch_controller.dart';

class LunchBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<LunchController>(() => LunchController());
  }
}
