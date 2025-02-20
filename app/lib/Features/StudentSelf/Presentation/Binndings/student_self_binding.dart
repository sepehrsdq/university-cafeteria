import 'package:get/get.dart';
import 'package:uni_project/Features/StudentSelf/Presentation/Controllers/student_self_controller.dart';

class StudentSelfBinding extends Bindings {
  @override
  void dependencies() {
    Get.lazyPut<StudentSelfController>(() => StudentSelfController());
  }
}
