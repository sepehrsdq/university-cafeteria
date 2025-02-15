import 'package:get/get.dart';
import 'package:uni_project/Features/SelfCarmandi/Presentation/Controllers/self_carmandi_controller.dart';

class SelfCarmandiBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<SelfCarmandiController>(() => SelfCarmandiController());
  }
}
