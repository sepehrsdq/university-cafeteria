import 'package:get/get.dart';
import 'package:uni_project/Features/Dinner/Presentation/Controllers/dinner_controller.dart';

class DinnerBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<DinnerController>(() => DinnerController());
  }
}
