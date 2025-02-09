import 'package:get/get.dart';
import 'package:uni_project/Features/BreakFast/Presentation/Controllers/break_fast_controller.dart';

class BreakFastBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<BreakFastController>(() => BreakFastController());
  }
}
