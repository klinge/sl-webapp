# SeglingController Refactoring Notes

## Issues Found During Test Creation:

### 1. Direct PDO Usage
- `saveMedlem()` and `deleteMedlemFromSegling()` use raw SQL
- Should use SeglingRepository methods instead

### 2. Hard Dependencies  
- Creates `new Segling()` and `new Roll()` in methods
- Should inject these via constructor

### 3. Untestable Input Handling
- `file_get_contents('php://input')` in `deleteMedlemFromSegling()`
- Should use PSR-7 request body

### 4. HTTP Concerns in Controller
- Direct `header()` and `exit()` calls
- Should use response objects or traits

## Recommended Changes:

1. **Add Repository Methods:**
   ```php
   // In SeglingRepository
   public function addMemberToSegling(int $seglingId, int $memberId, ?int $roleId = null): bool
   public function removeMemberFromSegling(int $seglingId, int $memberId): bool
   public function isMemberOnSegling(int $seglingId, int $memberId): bool
   ```

2. **Inject Dependencies:**
   ```php
   public function __construct(
       // ... existing params
       Roll $roll,
       Segling $segling  // or SeglingFactory
   )
   ```

3. **Use PSR-7 Request:**
   ```php
   // Instead of file_get_contents('php://input')
   $data = $this->request->getParsedBody();
   ```

4. **Use Response Traits:**
   ```php
   // Instead of header() + exit
   $this->redirectWithSuccess('segling-list', 'Message');
   ```

## Test Coverage Status:
- ✅ Basic methods tested
- ❌ Complex methods need refactoring first
- ❌ Integration with Segling/Roll models needs DI