import 'package:get/get.dart';
import 'package:uni_project/Features/FirstPage/Presentation/Controllers/first_page_controller.dart';

class FirstPageBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<FirstPageController>(() => FirstPageController());
  }
}
