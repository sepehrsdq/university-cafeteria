import 'package:get/get.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uni_project/Core/Constants/routes.dart';

class StaffMainController extends GetxController {
  var staffName = ''.obs;

  @override
  void onInit() {
    super.onInit();
    loadStaffInfo();
  }

  Future<void> loadStaffInfo() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    staffName.value = prefs.getString('staff_name') ?? 'کارمند';
  }

  void routeFoodManagement() {
    Get.toNamed(AppRoutes.foodManagement);
  }

  void routeStudentSelf() {
    Get.toNamed(AppRoutes.studentself);
  }

  void routeReports() {
    Get.toNamed(AppRoutes.reports);
  }

  void routeStaffTickets() {
    Get.toNamed(AppRoutes.stafftickets);
  }

  void addStudent() {
    Get.toNamed(AppRoutes.addstudnet);
  }
}
