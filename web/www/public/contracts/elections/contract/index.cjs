'use strict';
const __compactRuntime = require('@midnight-ntwrk/compact-runtime');
const expectedRuntimeVersionString = '0.9.0';
const expectedRuntimeVersion = expectedRuntimeVersionString.split('-')[0].split('.').map(Number);
const actualRuntimeVersion = __compactRuntime.versionString.split('-')[0].split('.').map(Number);
if (expectedRuntimeVersion[0] != actualRuntimeVersion[0]
     || (actualRuntimeVersion[0] == 0 && expectedRuntimeVersion[1] != actualRuntimeVersion[1])
     || expectedRuntimeVersion[1] > actualRuntimeVersion[1]
     || (expectedRuntimeVersion[1] == actualRuntimeVersion[1] && expectedRuntimeVersion[2] > actualRuntimeVersion[2]))
   throw new __compactRuntime.CompactError(`Version mismatch: compiled code expects ${expectedRuntimeVersionString}, runtime is ${__compactRuntime.versionString}`);
{ const MAX_FIELD = 52435875175126190479447740508185965837690552500527637822603658699938581184512n;
  if (__compactRuntime.MAX_FIELD !== MAX_FIELD)
     throw new __compactRuntime.CompactError(`compiler thinks maximum field value is ${MAX_FIELD}; run time thinks it is ${__compactRuntime.MAX_FIELD}`)
}

const _descriptor_0 = new __compactRuntime.CompactTypeUnsignedInteger(255n, 1);

const _descriptor_1 = new __compactRuntime.CompactTypeBytes(32);

const _descriptor_2 = new __compactRuntime.CompactTypeUnsignedInteger(1n, 1);

const _descriptor_3 = new __compactRuntime.CompactTypeUnsignedInteger(18446744073709551615n, 8);

class _ZswapCoinPublicKey_0 {
  alignment() {
    return _descriptor_1.alignment();
  }
  fromValue(value_0) {
    return {
      bytes: _descriptor_1.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_1.toValue(value_0.bytes);
  }
}

const _descriptor_4 = new _ZswapCoinPublicKey_0();

class _CredentialSubject_0 {
  alignment() {
    return _descriptor_1.alignment().concat(_descriptor_1.alignment().concat(_descriptor_1.alignment().concat(_descriptor_1.alignment().concat(_descriptor_3.alignment()))));
  }
  fromValue(value_0) {
    return {
      id: _descriptor_1.fromValue(value_0),
      first_name: _descriptor_1.fromValue(value_0),
      last_name: _descriptor_1.fromValue(value_0),
      national_identifier: _descriptor_1.fromValue(value_0),
      birth_timestamp: _descriptor_3.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_1.toValue(value_0.id).concat(_descriptor_1.toValue(value_0.first_name).concat(_descriptor_1.toValue(value_0.last_name).concat(_descriptor_1.toValue(value_0.national_identifier).concat(_descriptor_3.toValue(value_0.birth_timestamp)))));
  }
}

const _descriptor_5 = new _CredentialSubject_0();

const _descriptor_6 = new __compactRuntime.CompactTypeField();

class _CurvePoint_0 {
  alignment() {
    return _descriptor_6.alignment().concat(_descriptor_6.alignment());
  }
  fromValue(value_0) {
    return {
      x: _descriptor_6.fromValue(value_0),
      y: _descriptor_6.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_6.toValue(value_0.x).concat(_descriptor_6.toValue(value_0.y));
  }
}

const _descriptor_7 = new _CurvePoint_0();

class _Signature_0 {
  alignment() {
    return _descriptor_7.alignment().concat(_descriptor_7.alignment().concat(_descriptor_6.alignment()));
  }
  fromValue(value_0) {
    return {
      pk: _descriptor_7.fromValue(value_0),
      R: _descriptor_7.fromValue(value_0),
      s: _descriptor_6.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_7.toValue(value_0.pk).concat(_descriptor_7.toValue(value_0.R).concat(_descriptor_6.toValue(value_0.s)));
  }
}

const _descriptor_8 = new _Signature_0();

class _SignedCredentialSubject_0 {
  alignment() {
    return _descriptor_5.alignment().concat(_descriptor_8.alignment());
  }
  fromValue(value_0) {
    return {
      subject: _descriptor_5.fromValue(value_0),
      signature: _descriptor_8.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_5.toValue(value_0.subject).concat(_descriptor_8.toValue(value_0.signature));
  }
}

const _descriptor_9 = new _SignedCredentialSubject_0();

class _Ballot_0 {
  alignment() {
    return _descriptor_1.alignment().concat(_descriptor_1.alignment().concat(_descriptor_9.alignment()));
  }
  fromValue(value_0) {
    return {
      election_id: _descriptor_1.fromValue(value_0),
      candidate_id: _descriptor_1.fromValue(value_0),
      credential: _descriptor_9.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_1.toValue(value_0.election_id).concat(_descriptor_1.toValue(value_0.candidate_id).concat(_descriptor_9.toValue(value_0.credential)));
  }
}

const _descriptor_10 = new _Ballot_0();

const _descriptor_11 = new __compactRuntime.CompactTypeBoolean();

class _ContractAddress_0 {
  alignment() {
    return _descriptor_1.alignment();
  }
  fromValue(value_0) {
    return {
      bytes: _descriptor_1.fromValue(value_0)
    }
  }
  toValue(value_0) {
    return _descriptor_1.toValue(value_0.bytes);
  }
}

const _descriptor_12 = new _ContractAddress_0();

const _descriptor_13 = new __compactRuntime.CompactTypeUnsignedInteger(340282366920938463463374607431768211455n, 16);

class Contract {
  witnesses;
  constructor(...args_0) {
    if (args_0.length !== 1) {
      throw new __compactRuntime.CompactError(`Contract constructor: expected 1 argument, received ${args_0.length}`);
    }
    const witnesses_0 = args_0[0];
    if (typeof(witnesses_0) !== 'object') {
      throw new __compactRuntime.CompactError('first (witnesses) argument to Contract constructor is not an object');
    }
    if (typeof(witnesses_0.get_ballot) !== 'function') {
      throw new __compactRuntime.CompactError('first (witnesses) argument to Contract constructor does not contain a function-valued field named get_ballot');
    }
    this.witnesses = witnesses_0;
    this.circuits = {
      assert_is_admin: (...args_1) => {
        if (args_1.length !== 1) {
          throw new __compactRuntime.CompactError(`assert_is_admin: expected 1 argument (as invoked from Typescript), received ${args_1.length}`);
        }
        const contextOrig_0 = args_1[0];
        if (!(typeof(contextOrig_0) === 'object' && contextOrig_0.originalState != undefined && contextOrig_0.transactionContext != undefined)) {
          __compactRuntime.type_error('assert_is_admin',
                                      'argument 1 (as invoked from Typescript)',
                                      'elections.compact line 24 char 1',
                                      'CircuitContext',
                                      contextOrig_0)
        }
        const context = { ...contextOrig_0 };
        const partialProofData = {
          input: { value: [], alignment: [] },
          output: undefined,
          publicTranscript: [],
          privateTranscriptOutputs: []
        };
        const result_0 = this._assert_is_admin_0(context, partialProofData);
        partialProofData.output = { value: [], alignment: [] };
        return { result: result_0, context: context, proofData: partialProofData };
      },
      open_election: (...args_1) => {
        if (args_1.length !== 1) {
          throw new __compactRuntime.CompactError(`open_election: expected 1 argument (as invoked from Typescript), received ${args_1.length}`);
        }
        const contextOrig_0 = args_1[0];
        if (!(typeof(contextOrig_0) === 'object' && contextOrig_0.originalState != undefined && contextOrig_0.transactionContext != undefined)) {
          __compactRuntime.type_error('open_election',
                                      'argument 1 (as invoked from Typescript)',
                                      'elections.compact line 37 char 1',
                                      'CircuitContext',
                                      contextOrig_0)
        }
        const context = { ...contextOrig_0 };
        const partialProofData = {
          input: { value: [], alignment: [] },
          output: undefined,
          publicTranscript: [],
          privateTranscriptOutputs: []
        };
        const result_0 = this._open_election_0(context, partialProofData);
        partialProofData.output = { value: [], alignment: [] };
        return { result: result_0, context: context, proofData: partialProofData };
      },
      close_election: (...args_1) => {
        if (args_1.length !== 1) {
          throw new __compactRuntime.CompactError(`close_election: expected 1 argument (as invoked from Typescript), received ${args_1.length}`);
        }
        const contextOrig_0 = args_1[0];
        if (!(typeof(contextOrig_0) === 'object' && contextOrig_0.originalState != undefined && contextOrig_0.transactionContext != undefined)) {
          __compactRuntime.type_error('close_election',
                                      'argument 1 (as invoked from Typescript)',
                                      'elections.compact line 42 char 1',
                                      'CircuitContext',
                                      contextOrig_0)
        }
        const context = { ...contextOrig_0 };
        const partialProofData = {
          input: { value: [], alignment: [] },
          output: undefined,
          publicTranscript: [],
          privateTranscriptOutputs: []
        };
        const result_0 = this._close_election_0(context, partialProofData);
        partialProofData.output = { value: [], alignment: [] };
        return { result: result_0, context: context, proofData: partialProofData };
      },
      register_candidate: (...args_1) => {
        if (args_1.length !== 2) {
          throw new __compactRuntime.CompactError(`register_candidate: expected 2 arguments (as invoked from Typescript), received ${args_1.length}`);
        }
        const contextOrig_0 = args_1[0];
        const candidate_id_0 = args_1[1];
        if (!(typeof(contextOrig_0) === 'object' && contextOrig_0.originalState != undefined && contextOrig_0.transactionContext != undefined)) {
          __compactRuntime.type_error('register_candidate',
                                      'argument 1 (as invoked from Typescript)',
                                      'elections.compact line 47 char 1',
                                      'CircuitContext',
                                      contextOrig_0)
        }
        if (!(candidate_id_0.buffer instanceof ArrayBuffer && candidate_id_0.BYTES_PER_ELEMENT === 1 && candidate_id_0.length === 32)) {
          __compactRuntime.type_error('register_candidate',
                                      'argument 1 (argument 2 as invoked from Typescript)',
                                      'elections.compact line 47 char 1',
                                      'Bytes<32>',
                                      candidate_id_0)
        }
        const context = { ...contextOrig_0 };
        const partialProofData = {
          input: {
            value: _descriptor_1.toValue(candidate_id_0),
            alignment: _descriptor_1.alignment()
          },
          output: undefined,
          publicTranscript: [],
          privateTranscriptOutputs: []
        };
        const result_0 = this._register_candidate_0(context,
                                                    partialProofData,
                                                    candidate_id_0);
        partialProofData.output = { value: [], alignment: [] };
        return { result: result_0, context: context, proofData: partialProofData };
      },
      cast_vote: (...args_1) => {
        if (args_1.length !== 1) {
          throw new __compactRuntime.CompactError(`cast_vote: expected 1 argument (as invoked from Typescript), received ${args_1.length}`);
        }
        const contextOrig_0 = args_1[0];
        if (!(typeof(contextOrig_0) === 'object' && contextOrig_0.originalState != undefined && contextOrig_0.transactionContext != undefined)) {
          __compactRuntime.type_error('cast_vote',
                                      'argument 1 (as invoked from Typescript)',
                                      'elections.compact line 62 char 1',
                                      'CircuitContext',
                                      contextOrig_0)
        }
        const context = { ...contextOrig_0 };
        const partialProofData = {
          input: { value: [], alignment: [] },
          output: undefined,
          publicTranscript: [],
          privateTranscriptOutputs: []
        };
        const result_0 = this._cast_vote_0(context, partialProofData);
        partialProofData.output = { value: [], alignment: [] };
        return { result: result_0, context: context, proofData: partialProofData };
      }
    };
    this.impureCircuits = {
      assert_is_admin: this.circuits.assert_is_admin,
      open_election: this.circuits.open_election,
      close_election: this.circuits.close_election,
      register_candidate: this.circuits.register_candidate,
      cast_vote: this.circuits.cast_vote
    };
  }
  initialState(...args_0) {
    if (args_0.length !== 3) {
      throw new __compactRuntime.CompactError(`Contract state constructor: expected 3 arguments (as invoked from Typescript), received ${args_0.length}`);
    }
    const constructorContext_0 = args_0[0];
    const _trusted_issuer_public_key_0 = args_0[1];
    const _election_id_0 = args_0[2];
    if (typeof(constructorContext_0) !== 'object') {
      throw new __compactRuntime.CompactError(`Contract state constructor: expected 'constructorContext' in argument 1 (as invoked from Typescript) to be an object`);
    }
    if (!('initialPrivateState' in constructorContext_0)) {
      throw new __compactRuntime.CompactError(`Contract state constructor: expected 'initialPrivateState' in argument 1 (as invoked from Typescript)`);
    }
    if (!('initialZswapLocalState' in constructorContext_0)) {
      throw new __compactRuntime.CompactError(`Contract state constructor: expected 'initialZswapLocalState' in argument 1 (as invoked from Typescript)`);
    }
    if (typeof(constructorContext_0.initialZswapLocalState) !== 'object') {
      throw new __compactRuntime.CompactError(`Contract state constructor: expected 'initialZswapLocalState' in argument 1 (as invoked from Typescript) to be an object`);
    }
    if (!(typeof(_trusted_issuer_public_key_0) === 'object' && typeof(_trusted_issuer_public_key_0.x) === 'bigint' && _trusted_issuer_public_key_0.x >= 0 && _trusted_issuer_public_key_0.x <= __compactRuntime.MAX_FIELD && typeof(_trusted_issuer_public_key_0.y) === 'bigint' && _trusted_issuer_public_key_0.y >= 0 && _trusted_issuer_public_key_0.y <= __compactRuntime.MAX_FIELD)) {
      __compactRuntime.type_error('Contract state constructor',
                                  'argument 1 (argument 2 as invoked from Typescript)',
                                  'elections.compact line 28 char 1',
                                  'struct CurvePoint<x: Field, y: Field>',
                                  _trusted_issuer_public_key_0)
    }
    if (!(_election_id_0.buffer instanceof ArrayBuffer && _election_id_0.BYTES_PER_ELEMENT === 1 && _election_id_0.length === 32)) {
      __compactRuntime.type_error('Contract state constructor',
                                  'argument 2 (argument 3 as invoked from Typescript)',
                                  'elections.compact line 28 char 1',
                                  'Bytes<32>',
                                  _election_id_0)
    }
    const state_0 = new __compactRuntime.ContractState();
    let stateValue_0 = __compactRuntime.StateValue.newArray();
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    stateValue_0 = stateValue_0.arrayPush(__compactRuntime.StateValue.newNull());
    state_0.data = stateValue_0;
    state_0.setOperation('assert_is_admin', new __compactRuntime.ContractOperation());
    state_0.setOperation('open_election', new __compactRuntime.ContractOperation());
    state_0.setOperation('close_election', new __compactRuntime.ContractOperation());
    state_0.setOperation('register_candidate', new __compactRuntime.ContractOperation());
    state_0.setOperation('cast_vote', new __compactRuntime.ContractOperation());
    const context = {
      originalState: state_0,
      currentPrivateState: constructorContext_0.initialPrivateState,
      currentZswapLocalState: constructorContext_0.initialZswapLocalState,
      transactionContext: new __compactRuntime.QueryContext(state_0.data, __compactRuntime.dummyContractAddress())
    };
    const partialProofData = {
      input: { value: [], alignment: [] },
      output: undefined,
      publicTranscript: [],
      privateTranscriptOutputs: []
    };
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(0n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_4.toValue({ bytes: new Uint8Array(32) }),
                                                                            alignment: _descriptor_4.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(1n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_7.toValue({ x: 0n, y: 0n }),
                                                                            alignment: _descriptor_7.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(2n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(new Uint8Array(32)),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(3n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(0n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(4n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newMap(
                                        new __compactRuntime.StateMap()
                                      ).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(5n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newMap(
                                        new __compactRuntime.StateMap()
                                      ).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(6n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newMap(
                                        new __compactRuntime.StateMap()
                                      ).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(7n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(0n),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(8n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(0n),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    const tmp_0 = this._ownPublicKey_0(context, partialProofData);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(0n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_4.toValue(tmp_0),
                                                                            alignment: _descriptor_4.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(1n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_7.toValue(_trusted_issuer_public_key_0),
                                                                            alignment: _descriptor_7.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(2n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(_election_id_0),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    const tmp_1 = 0n;
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(3n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(tmp_1),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    const tmp_2 = 0n;
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(7n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(tmp_2),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    const tmp_3 = 0n;
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(8n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(tmp_3),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    state_0.data = context.transactionContext.state;
    return {
      currentContractState: state_0,
      currentPrivateState: context.currentPrivateState,
      currentZswapLocalState: context.currentZswapLocalState
    }
  }
  _persistentHash_0(value_0) {
    const result_0 = __compactRuntime.persistentHash(_descriptor_5, value_0);
    return result_0;
  }
  _persistentHash_1(value_0) {
    const result_0 = __compactRuntime.persistentHash(_descriptor_1, value_0);
    return result_0;
  }
  _ownPublicKey_0(context, partialProofData) {
    const result_0 = __compactRuntime.ownPublicKey(context);
    partialProofData.privateTranscriptOutputs.push({
      value: _descriptor_4.toValue(result_0),
      alignment: _descriptor_4.alignment()
    });
    return result_0;
  }
  _subject_hash_0(credentialSubject_0) {
    return this._persistentHash_0(credentialSubject_0);
  }
  _get_ballot_0(context, partialProofData) {
    const witnessContext_0 = __compactRuntime.witnessContext(ledger(context.transactionContext.state), context.currentPrivateState, context.transactionContext.address);
    const [nextPrivateState_0, result_0] = this.witnesses.get_ballot(witnessContext_0);
    context.currentPrivateState = nextPrivateState_0;
    if (!(typeof(result_0) === 'object' && result_0.election_id.buffer instanceof ArrayBuffer && result_0.election_id.BYTES_PER_ELEMENT === 1 && result_0.election_id.length === 32 && result_0.candidate_id.buffer instanceof ArrayBuffer && result_0.candidate_id.BYTES_PER_ELEMENT === 1 && result_0.candidate_id.length === 32 && typeof(result_0.credential) === 'object' && typeof(result_0.credential.subject) === 'object' && result_0.credential.subject.id.buffer instanceof ArrayBuffer && result_0.credential.subject.id.BYTES_PER_ELEMENT === 1 && result_0.credential.subject.id.length === 32 && result_0.credential.subject.first_name.buffer instanceof ArrayBuffer && result_0.credential.subject.first_name.BYTES_PER_ELEMENT === 1 && result_0.credential.subject.first_name.length === 32 && result_0.credential.subject.last_name.buffer instanceof ArrayBuffer && result_0.credential.subject.last_name.BYTES_PER_ELEMENT === 1 && result_0.credential.subject.last_name.length === 32 && result_0.credential.subject.national_identifier.buffer instanceof ArrayBuffer && result_0.credential.subject.national_identifier.BYTES_PER_ELEMENT === 1 && result_0.credential.subject.national_identifier.length === 32 && typeof(result_0.credential.subject.birth_timestamp) === 'bigint' && result_0.credential.subject.birth_timestamp >= 0n && result_0.credential.subject.birth_timestamp <= 18446744073709551615n && typeof(result_0.credential.signature) === 'object' && typeof(result_0.credential.signature.pk) === 'object' && typeof(result_0.credential.signature.pk.x) === 'bigint' && result_0.credential.signature.pk.x >= 0 && result_0.credential.signature.pk.x <= __compactRuntime.MAX_FIELD && typeof(result_0.credential.signature.pk.y) === 'bigint' && result_0.credential.signature.pk.y >= 0 && result_0.credential.signature.pk.y <= __compactRuntime.MAX_FIELD && typeof(result_0.credential.signature.R) === 'object' && typeof(result_0.credential.signature.R.x) === 'bigint' && result_0.credential.signature.R.x >= 0 && result_0.credential.signature.R.x <= __compactRuntime.MAX_FIELD && typeof(result_0.credential.signature.R.y) === 'bigint' && result_0.credential.signature.R.y >= 0 && result_0.credential.signature.R.y <= __compactRuntime.MAX_FIELD && typeof(result_0.credential.signature.s) === 'bigint' && result_0.credential.signature.s >= 0 && result_0.credential.signature.s <= __compactRuntime.MAX_FIELD)) {
      __compactRuntime.type_error('get_ballot',
                                  'return value',
                                  'elections.compact line 22 char 1',
                                  'struct Ballot<election_id: Bytes<32>, candidate_id: Bytes<32>, credential: struct SignedCredentialSubject<subject: struct CredentialSubject<id: Bytes<32>, first_name: Bytes<32>, last_name: Bytes<32>, national_identifier: Bytes<32>, birth_timestamp: Uint<0..18446744073709551615>>, signature: struct Signature<pk: struct CurvePoint<x: Field, y: Field>, R: struct CurvePoint<x: Field, y: Field>, s: Field>>>',
                                  result_0)
    }
    partialProofData.privateTranscriptOutputs.push({
      value: _descriptor_10.toValue(result_0),
      alignment: _descriptor_10.alignment()
    });
    return result_0;
  }
  _assert_is_admin_0(context, partialProofData) {
    __compactRuntime.assert(this._equal_0(_descriptor_4.fromValue(Contract._query(context,
                                                                                  partialProofData,
                                                                                  [
                                                                                   { dup: { n: 0 } },
                                                                                   { idx: { cached: false,
                                                                                            pushPath: false,
                                                                                            path: [
                                                                                                   { tag: 'value',
                                                                                                     value: { value: _descriptor_0.toValue(0n),
                                                                                                              alignment: _descriptor_0.alignment() } }] } },
                                                                                   { popeq: { cached: false,
                                                                                              result: undefined } }]).value),
                                          this._ownPublicKey_0(context,
                                                               partialProofData)),
                            'Only the election administrator can perform this action');
    return [];
  }
  _open_election_0(context, partialProofData) {
    this._assert_is_admin_0(context, partialProofData);
    const tmp_0 = 1n;
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(3n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(tmp_0),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    return [];
  }
  _close_election_0(context, partialProofData) {
    this._assert_is_admin_0(context, partialProofData);
    const tmp_0 = 0n;
    Contract._query(context,
                    partialProofData,
                    [
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(3n),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(tmp_0),
                                                                            alignment: _descriptor_0.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } }]);
    return [];
  }
  _register_candidate_0(context, partialProofData, candidate_id_0) {
    this._assert_is_admin_0(context, partialProofData);
    const candidate_commitment_0 = this._persistentHash_1(candidate_id_0);
    const disclosed_commitment_0 = candidate_commitment_0;
    const registry_ready_0 = _descriptor_2.fromValue(Contract._query(context,
                                                                     partialProofData,
                                                                     [
                                                                      { dup: { n: 0 } },
                                                                      { idx: { cached: false,
                                                                               pushPath: false,
                                                                               path: [
                                                                                      { tag: 'value',
                                                                                        value: { value: _descriptor_0.toValue(7n),
                                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                                      { popeq: { cached: false,
                                                                                 result: undefined } }]).value);
    if (this._equal_1(registry_ready_0, 1n)) {
      const already_registered_0 = _descriptor_2.fromValue(Contract._query(context,
                                                                           partialProofData,
                                                                           [
                                                                            { dup: { n: 0 } },
                                                                            { idx: { cached: false,
                                                                                     pushPath: false,
                                                                                     path: [
                                                                                            { tag: 'value',
                                                                                              value: { value: _descriptor_0.toValue(4n),
                                                                                                       alignment: _descriptor_0.alignment() } }] } },
                                                                            { idx: { cached: false,
                                                                                     pushPath: false,
                                                                                     path: [
                                                                                            { tag: 'value',
                                                                                              value: { value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                                                       alignment: _descriptor_1.alignment() } }] } },
                                                                            { popeq: { cached: false,
                                                                                       result: undefined } }]).value);
      __compactRuntime.assert(this._equal_2(already_registered_0, 0n),
                              'Candidate already registered');
    } else {
      Contract._query(context,
                      partialProofData,
                      [
                       { push: { storage: false,
                                 value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(7n),
                                                                              alignment: _descriptor_0.alignment() }).encode() } },
                       { push: { storage: true,
                                 value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(1n),
                                                                              alignment: _descriptor_2.alignment() }).encode() } },
                       { ins: { cached: false, n: 1 } }]);
    }
    Contract._query(context,
                    partialProofData,
                    [
                     { idx: { cached: false,
                              pushPath: true,
                              path: [
                                     { tag: 'value',
                                       value: { value: _descriptor_0.toValue(4n),
                                                alignment: _descriptor_0.alignment() } }] } },
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(1n),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } },
                     { ins: { cached: true, n: 1 } }]);
    const tmp_0 = 0n;
    Contract._query(context,
                    partialProofData,
                    [
                     { idx: { cached: false,
                              pushPath: true,
                              path: [
                                     { tag: 'value',
                                       value: { value: _descriptor_0.toValue(5n),
                                                alignment: _descriptor_0.alignment() } }] } },
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_3.toValue(tmp_0),
                                                                            alignment: _descriptor_3.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } },
                     { ins: { cached: true, n: 1 } }]);
    return [];
  }
  _cast_vote_0(context, partialProofData) {
    __compactRuntime.assert(this._equal_3(_descriptor_0.fromValue(Contract._query(context,
                                                                                  partialProofData,
                                                                                  [
                                                                                   { dup: { n: 0 } },
                                                                                   { idx: { cached: false,
                                                                                            pushPath: false,
                                                                                            path: [
                                                                                                   { tag: 'value',
                                                                                                     value: { value: _descriptor_0.toValue(3n),
                                                                                                              alignment: _descriptor_0.alignment() } }] } },
                                                                                   { popeq: { cached: false,
                                                                                              result: undefined } }]).value),
                                          1n),
                            'Election is not active');
    const ballot_0 = this._get_ballot_0(context, partialProofData);
    __compactRuntime.assert(this._equal_4(ballot_0.election_id,
                                          _descriptor_1.fromValue(Contract._query(context,
                                                                                  partialProofData,
                                                                                  [
                                                                                   { dup: { n: 0 } },
                                                                                   { idx: { cached: false,
                                                                                            pushPath: false,
                                                                                            path: [
                                                                                                   { tag: 'value',
                                                                                                     value: { value: _descriptor_0.toValue(2n),
                                                                                                              alignment: _descriptor_0.alignment() } }] } },
                                                                                   { popeq: { cached: false,
                                                                                              result: undefined } }]).value)),
                            'Ballot is for a different election');
    const credential_0 = ballot_0.credential;
    __compactRuntime.assert(this._equal_5(credential_0.subject.id,
                                          this._ownPublicKey_0(context,
                                                               partialProofData).bytes),
                            'Ballot credential does not belong to the sender');
    const credential_hash_0 = this._subject_hash_0(credential_0.subject);
    const ballot_commitment_0 = this._persistentHash_1(ballot_0.candidate_id);
    const disclosed_commitment_0 = ballot_commitment_0;
    const candidate_registered_0 = _descriptor_2.fromValue(Contract._query(context,
                                                                           partialProofData,
                                                                           [
                                                                            { dup: { n: 0 } },
                                                                            { idx: { cached: false,
                                                                                     pushPath: false,
                                                                                     path: [
                                                                                            { tag: 'value',
                                                                                              value: { value: _descriptor_0.toValue(4n),
                                                                                                       alignment: _descriptor_0.alignment() } }] } },
                                                                            { idx: { cached: false,
                                                                                     pushPath: false,
                                                                                     path: [
                                                                                            { tag: 'value',
                                                                                              value: { value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                                                       alignment: _descriptor_1.alignment() } }] } },
                                                                            { popeq: { cached: false,
                                                                                       result: undefined } }]).value);
    __compactRuntime.assert(this._equal_6(candidate_registered_0, 1n),
                            'Unknown candidate');
    const voter_nullifier_0 = credential_hash_0;
    const nullifier_ready_0 = _descriptor_2.fromValue(Contract._query(context,
                                                                      partialProofData,
                                                                      [
                                                                       { dup: { n: 0 } },
                                                                       { idx: { cached: false,
                                                                                pushPath: false,
                                                                                path: [
                                                                                       { tag: 'value',
                                                                                         value: { value: _descriptor_0.toValue(8n),
                                                                                                  alignment: _descriptor_0.alignment() } }] } },
                                                                       { popeq: { cached: false,
                                                                                  result: undefined } }]).value);
    if (this._equal_7(nullifier_ready_0, 1n)) {
      const has_voted_0 = _descriptor_2.fromValue(Contract._query(context,
                                                                  partialProofData,
                                                                  [
                                                                   { dup: { n: 0 } },
                                                                   { idx: { cached: false,
                                                                            pushPath: false,
                                                                            path: [
                                                                                   { tag: 'value',
                                                                                     value: { value: _descriptor_0.toValue(6n),
                                                                                              alignment: _descriptor_0.alignment() } }] } },
                                                                   { idx: { cached: false,
                                                                            pushPath: false,
                                                                            path: [
                                                                                   { tag: 'value',
                                                                                     value: { value: _descriptor_1.toValue(voter_nullifier_0),
                                                                                              alignment: _descriptor_1.alignment() } }] } },
                                                                   { popeq: { cached: false,
                                                                              result: undefined } }]).value);
      __compactRuntime.assert(this._equal_8(has_voted_0, 0n),
                              'Voter has already cast a ballot');
    } else {
      Contract._query(context,
                      partialProofData,
                      [
                       { push: { storage: false,
                                 value: __compactRuntime.StateValue.newCell({ value: _descriptor_0.toValue(8n),
                                                                              alignment: _descriptor_0.alignment() }).encode() } },
                       { push: { storage: true,
                                 value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(1n),
                                                                              alignment: _descriptor_2.alignment() }).encode() } },
                       { ins: { cached: false, n: 1 } }]);
    }
    const current_votes_0 = _descriptor_3.fromValue(Contract._query(context,
                                                                    partialProofData,
                                                                    [
                                                                     { dup: { n: 0 } },
                                                                     { idx: { cached: false,
                                                                              pushPath: false,
                                                                              path: [
                                                                                     { tag: 'value',
                                                                                       value: { value: _descriptor_0.toValue(5n),
                                                                                                alignment: _descriptor_0.alignment() } }] } },
                                                                     { idx: { cached: false,
                                                                              pushPath: false,
                                                                              path: [
                                                                                     { tag: 'value',
                                                                                       value: { value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                                                alignment: _descriptor_1.alignment() } }] } },
                                                                     { popeq: { cached: false,
                                                                                result: undefined } }]).value);
    const updated_votes_0 = ((t1) => {
                              if (t1 > 18446744073709551615n) {
                                throw new __compactRuntime.CompactError('elections.compact line 86 char 35: cast from Field or Uint value to smaller Uint value failed: ' + t1 + ' is greater than 18446744073709551615');
                              }
                              return t1;
                            })(current_votes_0 + 1n);
    Contract._query(context,
                    partialProofData,
                    [
                     { idx: { cached: false,
                              pushPath: true,
                              path: [
                                     { tag: 'value',
                                       value: { value: _descriptor_0.toValue(5n),
                                                alignment: _descriptor_0.alignment() } }] } },
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(disclosed_commitment_0),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_3.toValue(updated_votes_0),
                                                                            alignment: _descriptor_3.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } },
                     { ins: { cached: true, n: 1 } }]);
    Contract._query(context,
                    partialProofData,
                    [
                     { idx: { cached: false,
                              pushPath: true,
                              path: [
                                     { tag: 'value',
                                       value: { value: _descriptor_0.toValue(6n),
                                                alignment: _descriptor_0.alignment() } }] } },
                     { push: { storage: false,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(voter_nullifier_0),
                                                                            alignment: _descriptor_1.alignment() }).encode() } },
                     { push: { storage: true,
                               value: __compactRuntime.StateValue.newCell({ value: _descriptor_2.toValue(1n),
                                                                            alignment: _descriptor_2.alignment() }).encode() } },
                     { ins: { cached: false, n: 1 } },
                     { ins: { cached: true, n: 1 } }]);
    return [];
  }
  _equal_0(x0, y0) {
    {
      let x1 = x0.bytes;
      let y1 = y0.bytes;
      if (!x1.every((x, i) => y1[i] === x)) { return false; }
    }
    return true;
  }
  _equal_1(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  _equal_2(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  _equal_3(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  _equal_4(x0, y0) {
    if (!x0.every((x, i) => y0[i] === x)) { return false; }
    return true;
  }
  _equal_5(x0, y0) {
    if (!x0.every((x, i) => y0[i] === x)) { return false; }
    return true;
  }
  _equal_6(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  _equal_7(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  _equal_8(x0, y0) {
    if (x0 !== y0) { return false; }
    return true;
  }
  static _query(context, partialProofData, prog) {
    var res;
    try {
      res = context.transactionContext.query(prog, __compactRuntime.CostModel.dummyCostModel());
    } catch (err) {
      throw new __compactRuntime.CompactError(err.toString());
    }
    context.transactionContext = res.context;
    var reads = res.events.filter((e) => e.tag === 'read');
    var i = 0;
    partialProofData.publicTranscript = partialProofData.publicTranscript.concat(prog.map((op) => {
      if(typeof(op) === 'object' && 'popeq' in op) {
        return { popeq: {
          ...op.popeq,
          result: reads[i++].content,
        } };
      } else {
        return op;
      }
    }));
    if(res.events.length == 1 && res.events[0].tag === 'read') {
      return res.events[0].content;
    } else {
      return res.events;
    }
  }
}
function ledger(state) {
  const context = {
    originalState: state,
    transactionContext: new __compactRuntime.QueryContext(state, __compactRuntime.dummyContractAddress())
  };
  const partialProofData = {
    input: { value: [], alignment: [] },
    output: undefined,
    publicTranscript: [],
    privateTranscriptOutputs: []
  };
  return {
    get election_admin() {
      return _descriptor_4.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(0n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    },
    get trusted_issuer_public_key() {
      return _descriptor_7.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(1n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    },
    get active_election_id() {
      return _descriptor_1.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(2n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    },
    get election_active() {
      return _descriptor_0.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(3n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    },
    candidate_status: {
      isEmpty(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`isEmpty: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(4n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         'size',
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_3.toValue(0n),
                                                                                                                alignment: _descriptor_3.alignment() }).encode() } },
                                                         'eq',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      size(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`size: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_3.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(4n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        'size',
                                                        { popeq: { cached: true,
                                                                   result: undefined } }]).value);
      },
      member(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`member: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('member',
                                      'argument 1',
                                      'elections.compact line 16 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(4n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(key_0),
                                                                                                                alignment: _descriptor_1.alignment() }).encode() } },
                                                         'member',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      lookup(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`lookup: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('lookup',
                                      'argument 1',
                                      'elections.compact line 16 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_2.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(4n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_1.toValue(key_0),
                                                                                   alignment: _descriptor_1.alignment() } }] } },
                                                        { popeq: { cached: false,
                                                                   result: undefined } }]).value);
      },
      [Symbol.iterator](...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`iter: expected 0 arguments, received ${args_0.length}`);
        }
        const self_0 = state.asArray()[4];
        return self_0.asMap().keys().map(  (key) => {    const value = self_0.asMap().get(key).asCell();    return [      _descriptor_1.fromValue(key.value),      _descriptor_2.fromValue(value.value)    ];  })[Symbol.iterator]();
      }
    },
    vote_counts: {
      isEmpty(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`isEmpty: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(5n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         'size',
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_3.toValue(0n),
                                                                                                                alignment: _descriptor_3.alignment() }).encode() } },
                                                         'eq',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      size(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`size: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_3.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(5n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        'size',
                                                        { popeq: { cached: true,
                                                                   result: undefined } }]).value);
      },
      member(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`member: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('member',
                                      'argument 1',
                                      'elections.compact line 17 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(5n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(key_0),
                                                                                                                alignment: _descriptor_1.alignment() }).encode() } },
                                                         'member',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      lookup(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`lookup: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('lookup',
                                      'argument 1',
                                      'elections.compact line 17 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_3.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(5n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_1.toValue(key_0),
                                                                                   alignment: _descriptor_1.alignment() } }] } },
                                                        { popeq: { cached: false,
                                                                   result: undefined } }]).value);
      },
      [Symbol.iterator](...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`iter: expected 0 arguments, received ${args_0.length}`);
        }
        const self_0 = state.asArray()[5];
        return self_0.asMap().keys().map(  (key) => {    const value = self_0.asMap().get(key).asCell();    return [      _descriptor_1.fromValue(key.value),      _descriptor_3.fromValue(value.value)    ];  })[Symbol.iterator]();
      }
    },
    voter_nullifiers: {
      isEmpty(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`isEmpty: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(6n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         'size',
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_3.toValue(0n),
                                                                                                                alignment: _descriptor_3.alignment() }).encode() } },
                                                         'eq',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      size(...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`size: expected 0 arguments, received ${args_0.length}`);
        }
        return _descriptor_3.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(6n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        'size',
                                                        { popeq: { cached: true,
                                                                   result: undefined } }]).value);
      },
      member(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`member: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('member',
                                      'argument 1',
                                      'elections.compact line 18 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_11.fromValue(Contract._query(context,
                                                        partialProofData,
                                                        [
                                                         { dup: { n: 0 } },
                                                         { idx: { cached: false,
                                                                  pushPath: false,
                                                                  path: [
                                                                         { tag: 'value',
                                                                           value: { value: _descriptor_0.toValue(6n),
                                                                                    alignment: _descriptor_0.alignment() } }] } },
                                                         { push: { storage: false,
                                                                   value: __compactRuntime.StateValue.newCell({ value: _descriptor_1.toValue(key_0),
                                                                                                                alignment: _descriptor_1.alignment() }).encode() } },
                                                         'member',
                                                         { popeq: { cached: true,
                                                                    result: undefined } }]).value);
      },
      lookup(...args_0) {
        if (args_0.length !== 1) {
          throw new __compactRuntime.CompactError(`lookup: expected 1 argument, received ${args_0.length}`);
        }
        const key_0 = args_0[0];
        if (!(key_0.buffer instanceof ArrayBuffer && key_0.BYTES_PER_ELEMENT === 1 && key_0.length === 32)) {
          __compactRuntime.type_error('lookup',
                                      'argument 1',
                                      'elections.compact line 18 char 1',
                                      'Bytes<32>',
                                      key_0)
        }
        return _descriptor_2.fromValue(Contract._query(context,
                                                       partialProofData,
                                                       [
                                                        { dup: { n: 0 } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_0.toValue(6n),
                                                                                   alignment: _descriptor_0.alignment() } }] } },
                                                        { idx: { cached: false,
                                                                 pushPath: false,
                                                                 path: [
                                                                        { tag: 'value',
                                                                          value: { value: _descriptor_1.toValue(key_0),
                                                                                   alignment: _descriptor_1.alignment() } }] } },
                                                        { popeq: { cached: false,
                                                                   result: undefined } }]).value);
      },
      [Symbol.iterator](...args_0) {
        if (args_0.length !== 0) {
          throw new __compactRuntime.CompactError(`iter: expected 0 arguments, received ${args_0.length}`);
        }
        const self_0 = state.asArray()[6];
        return self_0.asMap().keys().map(  (key) => {    const value = self_0.asMap().get(key).asCell();    return [      _descriptor_1.fromValue(key.value),      _descriptor_2.fromValue(value.value)    ];  })[Symbol.iterator]();
      }
    },
    get candidate_registry_initialized() {
      return _descriptor_2.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(7n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    },
    get voter_nullifiers_initialized() {
      return _descriptor_2.fromValue(Contract._query(context,
                                                     partialProofData,
                                                     [
                                                      { dup: { n: 0 } },
                                                      { idx: { cached: false,
                                                               pushPath: false,
                                                               path: [
                                                                      { tag: 'value',
                                                                        value: { value: _descriptor_0.toValue(8n),
                                                                                 alignment: _descriptor_0.alignment() } }] } },
                                                      { popeq: { cached: false,
                                                                 result: undefined } }]).value);
    }
  };
}
const _emptyContext = {
  originalState: new __compactRuntime.ContractState(),
  transactionContext: new __compactRuntime.QueryContext(new __compactRuntime.ContractState().data, __compactRuntime.dummyContractAddress())
};
const _dummyContract = new Contract({ get_ballot: (...args) => undefined });
const pureCircuits = {};
const contractReferenceLocations = { tag: 'publicLedgerArray', indices: { } };
exports.Contract = Contract;
exports.ledger = ledger;
exports.pureCircuits = pureCircuits;
exports.contractReferenceLocations = contractReferenceLocations;
//# sourceMappingURL=index.cjs.map
